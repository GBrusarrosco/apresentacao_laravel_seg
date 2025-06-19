<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Teste SSRF</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/agate.min.css">

    <style>
        /* CSS Variáveis para fácil customização */
        :root {
            --bg-color: #f7f8fa;
            --card-bg-color: #ffffff;
            --primary-color: #4f46e5;
            --primary-hover-color: #4338ca;
            --text-color: #374151;
            --label-color: #111827;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --error-color: #ef4444;
            --font-family: 'Figtree', sans-serif;
        }

        body {
            font-family: var(--font-family);
            margin: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            padding: 2rem;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .card {
            background-color: var(--card-bg-color);
            padding: 1.5rem 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        h1, h2 {
            color: var(--label-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--label-color);
        }

        input[type="text"], select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-sizing: border-box;
            background-color: #f9fafb;
            color: var(--text-color);
            font-family: inherit;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px #c7d2fe;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
            font-family: 'Courier New', Courier, monospace;
            word-break: break-all;
        }

        button {
            width: 100%;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: var(--primary-hover-color);
        }

        #result-wrapper {
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            transition: border-color 0.3s;
        }

        #result-wrapper.success { border-color: var(--success-color); }
        #result-wrapper.error { border-color: var(--error-color); }

        pre#result {
            margin: 0;
            padding: 1rem;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 0.9em;
            max-height: 500px;
            overflow-y: auto;
            border-radius: 0.5rem; /* Para combinar com o wrapper e o tema do highlight.js */
        }

        /* Ajustes para telas menores */
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<main class="container">
    <section class="card">
        <h1>Dashboard de Teste SSRF</h1>
        <form id="ssrfForm">
            <div class="form-group">
                <label for="endpointSelect">Endpoint do Backend</label>
                <select id="endpointSelect" name="endpoint">
                    <option value="vulnerable" selected>Vulnerável (/fetch-url)</option>
                    <option value="secure">Seguro (/fetch-url-secure)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="urlInput">URL Alvo (para o SSRF)</label>
                <input type="text" id="urlInput" name="url" placeholder="Ex: http://localhost:8081/ip">
            </div>

            <div class="form-group">
                <label for="methodInput">Método HTTP</label>
                <select id="methodInput" name="method">
                    <option value="GET" selected>GET</option>
                    <option value="POST">POST</option>
                    <option value="PUT">PUT</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>

            <div class="form-group">
                <label for="dataInput">Dados (para POST/PUT)</label>
                <textarea id="dataInput" name="data" rows="3" placeholder="Ex: nome=Gabriel&status=ativo"></textarea>
            </div>

            <div class="form-group">
                <label for="headersInput">Cabeçalhos Customizados</label>
                <textarea id="headersInput" name="headers" rows="3" placeholder="Ex: X-API-Key: sua-chave"></textarea>
            </div>

            <button type="submit">Realizar Requisição SSRF</button>
        </form>
    </section>

    <section class="card">
        <h2>Resultado da Requisição</h2>
        <div id="result-wrapper">
            <pre><code id="result" class="language-json">Aguardando requisição...</code></pre>
        </div>
    </section>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
    // 3. Nosso JavaScript customizado para a página

    // Passa as URLs das rotas do Laravel para o JavaScript
    const vulnerableEndpointUrl = '{{ route("ssrf.fetch") }}';
    const secureEndpointUrl = '{{ route("ssrf.fetch.secure") }}';

    const resultWrapper = document.getElementById('result-wrapper');
    const resultElement = document.getElementById('result');

    document.getElementById('ssrfForm').addEventListener('submit', function(event) {
        event.preventDefault();
        const targetUrl = document.getElementById('urlInput').value;
        if (targetUrl) {
            fetchWithUrl(targetUrl);
        } else {
            resultElement.textContent = 'Por favor, insira uma URL Alvo.';
            resultWrapper.className = 'error';
        }
    });

    function fetchWithUrl(targetUrl) {
        const selectedEndpointOption = document.getElementById('endpointSelect').value;
        const method = document.getElementById('methodInput').value;
        const dataString = document.getElementById('dataInput').value;
        const headersString = document.getElementById('headersInput').value;

        let baseBackendUrl = (selectedEndpointOption === 'secure') ? secureEndpointUrl : vulnerableEndpointUrl;
        let requestUrlToOurBackend = `${baseBackendUrl}?url=${encodeURIComponent(targetUrl)}`;

        resultWrapper.className = ''; // Limpa o status visual (verde/vermelho)
        resultElement.textContent = 'Carregando...';

        if (selectedEndpointOption === 'vulnerable') {
            requestUrlToOurBackend += `&method=${encodeURIComponent(method)}`;

            // Processar dados (para POST/PUT)
            if ((method === 'POST' || method === 'PUT') && dataString.trim() !== '') {
                const dataParams = new URLSearchParams(dataString);
                for (const [key, value] of dataParams) {
                    requestUrlToOurBackend += `&data[${encodeURIComponent(key)}]=${encodeURIComponent(value)}`;
                }
            }

            // Processar cabeçalhos customizados
            if (headersString.trim() !== '') {
                const lines = headersString.trim().split('\n');
                for (const line of lines) {
                    if (line.includes(':')) {
                        const parts = line.split(':', 2);
                        const headerName = parts[0].trim();
                        const headerValue = parts[1].trim();
                        if (headerName && headerValue) {
                            requestUrlToOurBackend += `&headers[${encodeURIComponent(headerName)}]=${encodeURIComponent(headerValue)}`;
                        }
                    }
                }
            }
        }

        fetch(requestUrlToOurBackend)
            .then(response => {
                // Adiciona classe de sucesso ou erro à borda
                resultWrapper.className = response.ok ? 'success' : 'error';
                return response.text().then(text => ({
                    status: response.status,
                    ok: response.ok,
                    text: text
                }));
            })
            .then(data => {
                // Tenta formatar a resposta como JSON. Se falhar, mostra como texto puro.
                try {
                    const jsonObj = JSON.parse(data.text);
                    // Formata o JSON com 2 espaços de indentação
                    resultElement.textContent = JSON.stringify(jsonObj, null, 2);
                } catch (e) {
                    // A resposta não é JSON válido (ex: HTML de erro, ou texto simples)
                    resultElement.textContent = data.text;
                }
                // Aplica o syntax highlighting no elemento
                hljs.highlightElement(resultElement);
            })
            .catch(error => {
                console.error('Erro no fetch para o backend:', error);
                resultWrapper.className = 'error';
                resultElement.textContent = 'Erro de comunicação com o backend:\n' + error.message;
            });
    }
</script>
</body>
</html>
