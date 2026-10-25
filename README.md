# Survos Document Processing Bundle

Install with `composer require survos/document-processing-bundle`.

Thin Symfony configuration for `survos/document-processing`. Requires the Kit bundle. Register `Survos\Kit\SurvosKitBundle` and `Survos\DocumentProcessingBundle\SurvosDocumentProcessingBundle` (Flex discovery marker included).

```yaml
# config/packages/survos_document_processing.yaml
survos_document_processing:
    default_client: mediary
    clients:
        mediary:
            base_uri: '%env(DOCUMENT_PROCESSING_URL)%' # full URL ending /v1
            api_key: '%env(DOCUMENT_PROCESSING_KEY)%'
            timeout: 120
        mistral:
            base_uri: 'https://api.mistral.ai/v1'
            api_key: '%env(MISTRAL_API_KEY)%'
```

Autowire `Survos\DocumentProcessing\Client` for the default. Select another with `#[Autowire(service: 'survos_document_processing.client.mistral')]`. Client names are part of file-reference scope. No cloud fallback is configured. Every OCR call must select its model explicitly. The bundle has a dedicated contract serializer, so application serializer naming/group settings cannot alter the wire contract.

The bundle injects Symfony `http_client`; ensure application decorators do not automatically retry paid POST requests. Caching, queues and model routing remain application responsibilities. Keys are configuration-only; the standalone library never reads environment variables.

This package supplies a client, not Mediary's server endpoints. See the library README for supported operations and known compatibility limits.
