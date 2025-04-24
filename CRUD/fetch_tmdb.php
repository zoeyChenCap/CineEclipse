<?php
/*******w******** 
    
    Name:Zoey Chen
    Date:2025/04/24
    Description:This PHP script fetches movie or TV show details from the TMDb API based on a provided TMDb link. 
                It determines whether the link refers to a movie or TV show, retrieves relevant data (e.g., title, 
                language, release year, runtime, genres, poster URLs), and returns the information in JSON format. 
                It handles errors such as invalid links or missing data and provides feedback to the client.

****************/

require('../connect.php');

function fetchTMDbData($url) {
    $apiKey = '7d694c4e2a2366e2deeab57aba8c7597';
    $movieId = null;
    $type = null;

    // Decide the link is movie or tv based on the URL
    if (preg_match('/movie\/(\d+)/', $url, $matches)) {
        $movieId = $matches[1];
        $type = 'movie';
    } elseif (preg_match('/tv\/(\d+)/', $url, $matches)) {
        $movieId = $matches[1];
        $type = 'tv';
    } else {
        return null;
    }

    $apiUrl = "https://api.themoviedb.org/3/{$type}/{$movieId}?api_key={$apiKey}&language=en-US";

    $response = file_get_contents($apiUrl);
    if (!$response) return null;

    $data = json_decode($response, true);
    if (!$data) return null;

    // Unify the data structure for both movies and TV shows
    return [
        'title' => $data['title'] ?? $data['name'] ?? '',
        'original_language' => $data['original_language'] ?? '',
        'release_year' => isset($data['release_date']) ? intval(substr($data['release_date'], 0, 4)) :
                          (isset($data['first_air_date']) ? intval(substr($data['first_air_date'], 0, 4)) : null),
        'runtime' => $data['runtime'] ?? ($data['episode_run_time'][0] ?? null),
        'country' => $data['production_countries'][0]['iso_3166_1'] ?? ($data['origin_country'][0] ?? ''),
        'genres' => $data['genres'] ?? [],
        'poster_url' => !empty($data['poster_path']) ? "https://image.tmdb.org/t/p/original" . $data['poster_path'] : '',
        'poster_url_thumb' => !empty($data['poster_path']) ? "https://image.tmdb.org/t/p/w200" . $data['poster_path'] : '',
        'tmdb_type' => $type,  // movie or tv
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tmdb_link = trim($_POST['tmdb_link'] ?? '');

    if (!empty($tmdb_link)) {
        $movieData = fetchTMDbData($tmdb_link);
        if ($movieData) {
            echo json_encode([
                'success' => true,
                'title' => $movieData['title'] ?? '',
                'language' => $movieData['original_language'] ?? '',
                'release_year' => $movieData['release_year'] ?? '',
                'runtime' => $movieData['runtime'] ?? '',
                'country' => $movieData['country'] ?? '',
                'genres' => array_map(function($g) {
                    return $g['name'];
                }, $movieData['genres']),
                'type' => $movieData['tmdb_type'] ?? '',
                'poster_url' => $movieData['poster_url'] ?? '', // Add the original poster URL
                'poster_url_thumb' => $movieData['poster_url_thumb'] ?? '', // Add the thumbnail poster URL
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Movie not found']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid TMDb link']);
    }
}
?>
