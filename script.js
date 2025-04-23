document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM已完全加载"); // 测试是否执行
    // 初始化表单值
    document.getElementById("sort_column").value = "release_year";
    document.getElementById("sort_order").value = "DESC";
    
    // 初始加载数据
    fetchMovies('release_year', 'DESC');
    
    // 表单提交处理
    document.getElementById("sortMoviesForm").addEventListener("submit", function (event) {
        event.preventDefault();
        const sortColumn = document.getElementById('sort_column').value;
        const sortOrder = document.getElementById('sort_order').value;
        fetchMovies(sortColumn, sortOrder);
    });
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function decodeHtml(str) {
    const element = document.createElement('div');
    if (str) {
        element.innerHTML = str;
        return element.innerText || element.textContent;
    }
    return '';
}

function fetchMovies(sortColumn, sortOrder) {
    const moviesContainer = document.getElementById("moviesContainer");
    moviesContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div><p>Loading movies...</p></div>';

    fetch(`backstage.php?sort_column=${sortColumn}&sort_order=${sortOrder}`)
        .then(response => {
            console.log("收到响应，状态码:", response.status); // 添加这行
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error("Invalid response format");
            }
            return response.json();
        })
        .then(data => {
            console.log("Fetched movies:", data);  // 👈 看看有没有 poster_url_thumb
            if (!Array.isArray(data)) {
                throw new Error("Invalid data format");
            }
            
            renderMovies(data);
        })
        .catch(error => {
            console.error("Fetch error:", error);
            moviesContainer.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load movies. 
                    <small>${error.message}</small>
                </div>
            `;
        });
}

function formatRuntime(runtime) {
    if (!runtime || isNaN(runtime)) return 'N/A';  // 防止空值或非数字
    const minutes = Number(runtime);  // 确保 runtime 是数字
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
}

function renderMovies(movies) {
    const moviesContainer = document.getElementById("moviesContainer");
    moviesContainer.innerHTML = "";

    if (movies.length === 0) {
        moviesContainer.innerHTML = "<p>No movies found.</p>";
        return;
    }

    movies.forEach(movie => {
        console.log("当前电影数据:", movie); // 添加这行
        console.log("缩略图路径:", movie.poster_url_thumb); // 添加这行

        const movieElement = document.createElement('div');
        movieElement.classList.add('movie_card');
        movieElement.innerHTML = `
            <div class="movie_info">
                <div class="info_with_poster">
                    <div class="text_info">
                        <h2>${decodeHtml(escapeHtml(movie.title))}</h2>
                        <p><strong>Type:</strong> ${decodeHtml(escapeHtml(movie.type))}</p>
                        <p><strong>Runtime:</strong> ${formatRuntime(movie.runtime)}</p>
                        <p><strong>Release Year:</strong> ${decodeHtml(escapeHtml(movie.release_year))}</p>
                        <p><strong>Language:</strong> ${decodeHtml(escapeHtml(movie.language))}</p>
                        <p><strong>Genre:</strong> ${decodeHtml(escapeHtml(movie.genre_name || 'Unknown'))}</p>
                    </div>
                    ${movie.poster_url_thumb ? `
                    <div class="poster_container">
                        <img src="${movie.poster_url_thumb}" alt="Movie Poster">
                    </div>
                    ` : ''}
                </div>
                ${movie.tmdb_link ? `
                <p><strong>TMDb Link:</strong> 
                    <a href="${decodeHtml(escapeHtml(movie.tmdb_link))}" target="_blank">${decodeHtml(escapeHtml(movie.tmdb_link))}</a>
                </p>
                ` : ''}
                <div class="double_buttons">
                    <button class="edit-btn" onclick="location.href='CRUD/edit.php?id=${decodeHtml(escapeHtml(movie.movie_id))}'">Edit</button>
                    <button class="delete-btn" onclick="if(confirm('Are you sure you want to delete this movie?')) location.href='CRUD/delete.php?id=${escapeHtml(movie.movie_id)}'">Delete</button>
                </div>
            </div>
        `;
        moviesContainer.appendChild(movieElement);
    });
}

// Handle the reset button click
if (window.location.pathname === '/index.php') {
document.getElementById("resetBtn").addEventListener("click", function() {
    // Clear all input fields and reload the page to reset the filter
    document.querySelector('[name="search"]').value = '';
    document.querySelector('[name="release_year"]').value = '';
    document.querySelector('[name="genre_id"]').value = '';
    
    // Reload the page to reset search query
    window.location.href = 'index.php';
});
}