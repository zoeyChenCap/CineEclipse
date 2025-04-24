/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This JavaScript code enables autofill functionality for a movie form using a TMDb link. 
                It fetches movie details via an API, populates form fields (e.g., title, language, release year, 
                runtime, etc.), and updates the poster preview. It also validates uploaded poster files and 
                displays a preview of the selected image.

****************/

document.addEventListener('DOMContentLoaded', function () {
    const autofillBtn = document.getElementById('autofillBtn');
    const posterInput = document.getElementById('poster');
    const posterPreviewContainer = document.getElementById('poster_preview_container');
    const posterPreview = document.getElementById('poster_preview');

    if (!autofillBtn || !posterInput) return;

    // Autofill logic (already exists)
    autofillBtn.addEventListener('click', function () {
        const tmdbLink = document.getElementById('tmdb_link').value;

        if (!tmdbLink) {
            alert('Please enter a TMDb link.');
            return;
        }

        fetch('fetch_tmdb.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `tmdb_link=${encodeURIComponent(tmdbLink)}`
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('HTTP error: ' + res.status);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('title').value = data.title || '';
                document.getElementById('language').value = data.language || '';
                document.getElementById('release_year').value = data.release_year || '';
                document.getElementById('runtime').value = data.runtime || '';
                document.getElementById('type').value = data.type || '';
                document.getElementById('country').value = data.country || '';
                document.getElementById('tmdb_genres_label').textContent = data.genres.join(', ');
                document.getElementById('poster_from_tmdb').value = data.poster_url || '';

                // Update poster preview with TMDb poster
                posterPreviewContainer.style.display = data.poster_url_thumb ? 'block' : 'none';
                if (data.poster_url_thumb) {
                    posterPreview.src = data.poster_url_thumb;
                }
            } else {
                alert(data.error || 'Failed to fetch TMDb data.');
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('Network error or invalid JSON received.');
        });
    });

    // Update poster preview when a new file is uploaded
    posterInput.addEventListener('change', function () {
        const file = posterInput.files[0];
        if (file) {
        const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Invalid file type. Please upload a JPG, PNG, or WEBP image.');
                posterInput.value = ''; // Clear the input
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                posterPreview.src = e.target.result; // Update preview with the uploaded file
                posterPreviewContainer.style.display = 'block'; // Ensure the preview container is visible
            };
            reader.readAsDataURL(file); // Read the file as a data URL
        }
    });
});