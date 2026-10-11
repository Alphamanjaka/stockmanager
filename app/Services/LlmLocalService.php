<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service pour interagir avec un modèle de langage local (LM Studio en Dev / Ollama en Prod).
 */
class LlmLocalService
{
    private string $url;
    private string $model;
    private bool $isOllama;

    public function __construct()
    {
        // En Dev (Laragon) : http://localhost:1234/v1/chat/completions
        // En Prod (Docker) : http://ollama:11434/api/chat
        $this->url = env('LLM_URL', 'http://localhost:1234/v1/chat/completions');
        $this->model = env('LLM_MODEL', 'llama3.2:1b');

        // Détecte automatiquement si on tape sur Ollama ou LM Studio pour adapter le traitement JSON
        $this->isOllama = str_contains($this->url, '11434');
    }

    /**
     * Exécute une requête de chat structurée.
     *
     * @param string $systemPrompt Les consignes de comportement pour l'IA.
     * @param string $userPrompt La demande et les données de l'utilisateur.
     * @param array $params Paramètres optionnels (ex: temperature).
     * @return string|null La réponse textuelle du modèle ou null en cas d'échec.
     */
    public function generate(string $systemPrompt, string $userPrompt, array $params = []): ?string
    {
        // Structure standard de Chat (Mots-clés 'messages', 'role', 'content')
        // Recommandé pour Llama 3.2 et Gemma car cela sépare strictement les ordres du contexte.
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'stream' => false,
            'temperature' => $params['temperature'] ?? 0.1,
        ];

        // Format spécifique si vous utilisez l'ancienne API 'generate' d'Ollama
        if ($this->isOllama && str_contains($this->url, 'generate')) {
            $data = [
                'model' => $this->model,
                'prompt' => $systemPrompt . "\n\n" . $userPrompt,
                'stream' => false,
                'options' => ['temperature' => $params['temperature'] ?? 0.1]
            ];
        }

        try {
            $response = Http::timeout(60)->post($this->url, $data);

            if ($response->successful()) {
                $body = $response->json();

                // Extraction du texte selon le moteur qui répond
                if ($this->isOllama) {
                    return trim($body['message']['content'] ?? $body['response'] ?? '');
                }

                // Extraction format LM Studio / OpenAI
                return trim($body['choices'][0]['message']['content'] ?? '');
            }

            Log::error("Échec de la connexion au LLM: " . $response->status() . " - " . $response->body());
            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Erreur de connexion au LLM local (Vérifie que LM Studio/Ollama est démarré): ", ['exception' => $e->getMessage()]);
            return null;
        } catch (\Exception $e) {
            Log::error("Erreur inattendue lors de l'appel au LLM: ", ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Analyse les données fournies et force le modèle à renvoyer une réponse courte pour aider à la décision.
     *
     * @param array $donnees Les données structurées à analyser.
     * @return string|null La réponse synthétique du modèle.
     */
    public function analyserDonnees(array $donnees): ?string
    {
        $systemPrompt = "Tu es un expert en analyse décisionnelle concis.
         Ta seule tâche est de lire les données fournies et d'en déduire la meilleure action possible.
          TU DOIS RESPECTER LA LONGUEUR MAXIMALE FIXÉE (20 mots). NE PAS donner d'explications détaillées, SEULEMENT LE CONSEIL FINAL.";

        $userPrompt = "Veuillez analyser ces données et formuler une conclusion d'aide à la décision de moins de 20 mots : " . json_encode($donnees);

        return $this->generate($systemPrompt, $userPrompt, ['temperature' => 0.1]);
    }
}