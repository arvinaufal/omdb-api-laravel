<?php

namespace App\Http\Controllers;

use Hamcrest\Arrays\IsArray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    public function getPopularMovies(Request $request) {
        // get api key from env or use default key (my key)
        $apiKey = config('app.OMDB_API_KEY', 'eacaff60');

        // Check if API key is available or return failed if its not existed
        if (!$apiKey) {
            return response()->json(['error' => 'OMDB API key not configured.'], 500);
        }

        // Define cache key
        $cacheKey = 'popular_movies_desc';

        if ($request->query('sort') && $request->query('sort') == 'asc') {
            $cacheKey = 'popular_movies_asc';
        }

        // Fetch data from cache or retrieve and cache if not available
        $allMovies = Cache::remember($cacheKey, 3600, function () use ($apiKey) {
            // Array of alphabets from 'a' to 'z'
            $alphabets = range('a', 'z');
            $allMovies = [];

            foreach ($alphabets as $alphabet) {
                // Make HTTP request to OMDB API for each alphabet
                $response = Http::get("https://www.omdbapi.com/?apikey=$apiKey&t=$alphabet&y=2024&plot=full");

                // Check if request was successful
                if ($response->successful()) {
                    $movie = $response->json();

                    if (isset($movie) && is_array($movie)) {
                            // Convert rating to a number for sorting
                            $rating = $movie['imdbRating'];
                            if ($rating == 'N/A') {
                                $rating = 0; // Treat 'N/A' as 0
                            } else {
                                // Extract numeric part from "7.9/10" format
                                preg_match('/\d+\.\d+/', $rating, $matches);
                                $rating = $matches ? (float) $matches[0] : 0;
                            }

                            $allMovies[] = [
                                'title' => $movie['Title'],
                                'year' => $movie['Year'],
                                'poster' => $movie['Poster'],
                                'rating' => $rating, // Store numeric rating
                            ];
                        
                    }
                } else {
                    return response()->json(['error' => 'Failed to fetch data from OMDB API'], 500);
                }
            }

            return $allMovies;
        });


        // Sort movies based on rating (descending order)
        $sortType = $request->query('sort', 'desc');
        if ($sortType == 'desc') {
            usort($allMovies, function ($a, $b) {
                return $b['rating'] <=> $a['rating'];
            });
        } elseif ($sortType == 'asc') {
            usort($allMovies, function ($a, $b) {
                return $a['rating'] <=> $b['rating'];
            });
        }

        return response()->json($allMovies);
    }


    public function getMovieDetail(Request $request)
    {
        // get api key from env or use default key (my key)
        $apiKey = config('app.OMDB_API_KEY', 'eacaff60');
        
        // get params id from route
        $movieId = $request->query('id');

        // Get detail data from api
        $response = Http::get("https://www.omdbapi.com/?apikey=$apiKey&i=$movieId&plot=full");

        // set default response
        $movies = [];

        // conditional based on api response
        if ($response->successful()) {
            $movies = $response->json();

            // if the response is false from api
            if ($movies["Response"] == "False") {
                return response()->json(['error' => $movies["Error"]], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to fetch data from OMDB API'], 500);
        }

        // return as json
        return response()->json($movies);
    }

    public function searchMovies(Request $request)
    {
        // get api key from env or use default key (my key)
        $apiKey = config('app.OMDB_API_KEY', 'eacaff60');

        // get query from params
        $query = $request->query('query');

        // Ambil hasil pencarian film dari API OMDB
        $response = Http::get("https://www.omdbapi.com/?apikey=$apiKey&s=$query");

        // set default response
        $movies = [];

        // conditional based on api response
        if ($response->successful()) {
            $movies = $response->json();

            // if the response is false from api
            if (isset($movies["Response"]) && $movies["Response"] == "False") {
                return response()->json(['error' => $movies["Error"]], 500);
            }
        } else {
            return response()->json(['error' => 'Failed to fetch data from OMDB API'], 500);
        }

        return response()->json($movies);
    }
}
