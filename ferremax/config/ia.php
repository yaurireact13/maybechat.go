<?php

/**
 * Asistente de reportes IA + Chat IA (FerreMax)
 * Todos los proveedores usan el formato /chat/completions compatible con OpenAI.
 */
return [
    'presets' => [
        'gemini' => ['nombre' => 'Google Gemini (gratis)', 'url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'modelo' => 'gemini-3.5-flash-lite', 'clave' => true, 'ayuda' => 'Clave gratis en aistudio.google.com/apikey'],
        'groq' => ['nombre' => 'Groq (gratis)', 'url' => 'https://api.groq.com/openai/v1', 'modelo' => 'llama-3.3-70b-versatile', 'clave' => true, 'ayuda' => 'Clave gratis en console.groq.com/keys'],
        'openrouter' => ['nombre' => 'OpenRouter (modelos :free)', 'url' => 'https://openrouter.ai/api/v1', 'modelo' => 'meta-llama/llama-3.3-70b-instruct:free', 'clave' => true, 'ayuda' => 'Clave gratis en openrouter.ai/keys'],
        'ollama' => ['nombre' => 'Ollama (local, 100 % gratis y privado)', 'url' => 'http://localhost:11434/v1', 'modelo' => 'qwen2.5-coder:7b', 'clave' => false, 'ayuda' => 'Instala Ollama en tu PC y ejecuta: ollama pull qwen2.5-coder:7b'],
        'otro' => ['nombre' => 'Otro (compatible con OpenAI)', 'url' => '', 'modelo' => '', 'clave' => true, 'ayuda' => 'Cualquier servicio con API /chat/completions (OpenAI, LM Studio, etc.)'],
    ],
];
