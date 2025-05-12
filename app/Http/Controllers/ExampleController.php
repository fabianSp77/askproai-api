<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="AskProAI API Dokumentation",
 *      description="Beispiel API-Dokumentation"
 * )
 *
 * @OA\Server(url="/api")
 */
class ExampleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/example",
     *     summary="Beispielroute",
     *     @OA\Response(
     *         response=200,
     *         description="Erfolgreiche Antwort",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Hallo von AskProAI!")
     *         )
     *     )
     * )
     */
    public function example()
    {
        return response()->json(['message' => 'Hallo von AskProAI!']);
    }
}
