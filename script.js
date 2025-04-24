document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM已完全加载"); // Test if the script is executing
    // Initialize form values
    document.getElementById("sort_column").value = "release_year";
    document.getElementById("sort_order").value = "DESC";
    
    // Initial data load
    fetchMovies('release_year', 'DESC');
    
    // Handle form submission
    document.getElementById("sortMoviesForm").addEventListener("submit", function (event) {
        event.preventDefault();
        const sortColumn = document.getElementById('sort_column').value;
        const sortOrder = document.getElementById('sort_order').value;
        fetchMovies(sortColumn, sortOrder);
    });
});

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
    if (!runtime || isNaN(runtime)) return 'N/A';  // Prevent empty or non-numeric values
    const minutes = Number(runtime);  // Ensure runtime is a number
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
const movieElement = document.createElement('div');
        movieElement.classList.add('movie_card');
        movieElement.innerHTML = `
            <div class="movie_info">
                <div class="info_with_poster">
                    <div class="text_info">
                        <h2>${decodeHtml(movie.title)}</h2>
                        <p><strong>Type:</strong> ${decodeHtml(movie.type)}</p>
                        <p><strong>Runtime:</strong> ${formatRuntime(movie.runtime)}</p>
                        <p><strong>Release Year:</strong> ${decodeHtml(movie.release_year)}</p>
                        <p><strong>Language:</strong> ${decodeHtml(movie.language)}</p>
                        <p><strong>Genre:</strong> ${decodeHtml(movie.genre_name || 'Unknown')}</p>
                    </div>
                    ${movie.poster_url_thumb ? `
    <div class="poster_container">
        <img src="${movie.poster_url_thumb}" alt="Movie Poster">
    </div>
` : ''}
                    </div>
                ${movie.tmdb_link ? `
                <p><strong>TMDb Link:</strong> 
                    <a href="${decodeHtml(movie.tmdb_link)}" target="_blank">${decodeHtml(movie.tmdb_link)}</a>
                </p>
               ` : ''}
                <div class="double_buttons">
                    <button class="edit-btn" onclick="location.href='CRUD/edit.php?id=${decodeHtml(movie.movie_id)}'">Edit</button>
                    <button class="delete-btn" onclick="if(confirm('Are you sure you want to delete this movie?')) location.href='CRUD/delete.php?id=${decodeHtml(movie.movie_id)}'">Delete</button>
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
    document.queryelector('[name="release_year"]').value =// Clear all input fields and reload the page to reset the filter
 '';
    document.querySelector('[name="genre_id"]').value = '';
    
    // Reload the page to reset search query
    widow.location.href = 'index.php';
});
}// Clear all input fields and reload the page to reset the filter
