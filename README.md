# 🎬 YouTubeDownloader (PHP)

Uma classe moderna e poderosa para **baixar vídeos do YouTube** com metadados completos — simples de usar, robusta e segura.  
Desenvolvida em **PHP puro**, com integração à **API do Clipto**, e escrita com boas práticas de **tipagem, exceções e validação**.

---

## 🚀 Funcionalidades

✅ Obtém **título**, **autor**, **thumbnail**, **duração** e **formatos disponíveis**  
✅ Seleciona automaticamente a **melhor qualidade** de download  
✅ Faz o download e salva o arquivo com nome **sanitizado**  
✅ Tudo via **cURL**, sem dependências externas  
✅ Código totalmente **tipado (PHP 8+)** e **orientado a objetos**

---

## 🧩 Estrutura da Classe

A classe principal é `App\Services\YouTubeDownloader`, e funciona em **3 etapas simples**:

1. **Instanciar** com a URL do vídeo  
2. **Buscar os dados** do vídeo  
3. **Baixar o melhor formato disponível**

---

## ⚙️ Exemplo de Uso

```php
require './vendor/autoload.php';

use App\Services\YouTubeDownloader;

try {
    // Instancia o downloader com a URL do vídeo
    $downloader = new YouTubeDownloader('https://youtube.com/watch?v=L8UEqSn-BVc');

    // Busca os metadados do vídeo
    $downloader->fetchVideoData();

    // Exibe informações detalhadas
    echo $downloader->getMetadata();

    // Faz o download na melhor qualidade disponível
    echo $downloader->downloadBestQuality();

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
📦 Requisitos
PHP 8.0+

Extensão cURL habilitada

Acesso à internet para consumo da API

💡 Dica
Você pode integrar este projeto facilmente em qualquer aplicação PHP (como bots, automações ou painéis administrativos).
A classe foi escrita para ser reutilizável e facilmente estendida.

📜 Créditos e Agradecimentos
Desenvolvido com 💚 por @androplaybr157
Ideia, código e documentação originais criados com o objetivo de incentivar o aprendizado em PHP moderno e facilitar a automação de tarefas com APIs.

Se este projeto te ajudou, deixe uma ⭐ no repositório e compartilhe com outros devs!
Seu apoio motiva a continuar criando soluções open-source de qualidade 🚀
