<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    public function apiConfiguration(Request $request)
    {
        $method = $request->method;
        if ($method == 'get') {
            $url = $request->url;
            if ($request->tokenType == 'Bearer') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $request->token,
                ])->$method($url);
            } elseif ($request->tokenType == 'ApiKey') {
                $response = Http::withHeaders([
                    $request->key => $request->value,
                ])->$method($url);
            }

            if ($response->successful()) {
                // Process the response data
                $data = $response->json();

                return response()->json($data);
            } else {
                return response()->json(['error' => 'API request failed'], 500);
            }
        } elseif ($method == 'post') {
            $url = $request->url;
            $headers = [];

            if ($request->tokenType == 'Bearer') {
                $headers['Authorization'] = 'Bearer ' . $request->token;
            } elseif ($request->tokenType == 'ApiKey') {
                $headers[$request->key] = $request->value;
            }

            $requestBody = [];
            // echo $request->details;
            $data = $request->details;
            $detai = json_decode($data, true);
            print_r($detai);
            
            if(!is_array($detai)) {
                return response()->json(['message' => 'Sorry! Something went wrong.']);
            }
            foreach ($detai as $key => $value) {
                $requestBody[$key] = $value;
            }

            $response = Http::withHeaders($headers)->post($url, $requestBody);

            if ($response->successful()) {
                $statusCode = $response->status();
                $responseData = $response->json();
                $responseMessage = "Request was successful $statusCode";

                if (isset($responseData['message'])) {
                    $responseMessage .= ": " . $responseData['message'];
                }
            } else {
                $statusCode = $response->status();
                $errorResponse = $response->json();
                $responseMessage = "Request failed with status code $statusCode";

                if (isset($errorResponse['message'])) {
                    $responseMessage .= ": " . $errorResponse['message'];
                }

                return response()->json(['message' => $responseMessage], $statusCode);
            }

            return response()->json(['message' => $responseMessage]);
        } elseif ($method == 'delete') {
            $url = $request->url;
            $headers = [];

            if ($request->tokenType == 'Bearer') {
                $headers['Authorization'] = 'Bearer ' . $request->token;
            } elseif ($request->tokenType == 'ApiKey') {
                $headers[$request->key] = $request->value;
            }

            $requestBody = [];
            // echo $request->details;
            $data = $request->details;
            $detai = json_decode($data, true);

            foreach ($detai as $key => $value) {
                $requestBody[$key] = $value;
            }
            $response = Http::withHeaders($headers)->delete($url, $requestBody);

            if ($response->successful()) {
                $responseData = $response->json();
                $responseMessage = "DELETE request was successful";

                if (isset($responseData['message'])) {
                    $responseMessage .= ": " . $responseData['message'];
                }
            } else {
                $statusCode = $response->status();
                $errorResponse = $response->json();
                $responseMessage = "DELETE request failed with status code $statusCode";

                if (isset($errorResponse['message'])) {
                    $responseMessage .= ": " . $errorResponse['message'];
                }

                return response()->json(['message' => $responseMessage], $statusCode);
            }

            return response()->json(['message' => $responseMessage]);
        }
    }
}
