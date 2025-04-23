document.addEventListener('DOMContentLoaded', function () {
    const autofillBtn = document.getElementById('autofillBtn');

    if (!autofillBtn) return;

    autofillBtn.addEventListener('click', function () {
        const tmdbLink = document.getElementById('tmdb_link').value;

        if (!tmdbLink.trim()) {
            alert('Please enter a TMDb link.');
            return;
        }

        const formData = new FormData();
        formData.append('tmdb_link', tmdbLink);

        fetch('fetch_tmdb.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('title').value = data.title || '';
                    document.getElementById('language').value = data.language || '';
                    document.getElementById('release_year').value = data.release_year || '';
                    document.getElementById('runtime').value = data.runtime || '';
                    document.getElementById('type').value = data.type || '';
                    document.getElementById('country').value = data.country || '';
                    const genreLabel = document.getElementById('tmdb_genres_label');
                    if (genreLabel) {
                        genreLabel.textContent = data.genres.join(', ');
                    }
                } else {
                    alert(data.error || 'Failed to fetch TMDb data.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Network error.');
            });
    });
});
