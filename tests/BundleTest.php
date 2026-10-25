<?php

declare(strict_types=1);

namespace Survos\DocumentProcessingBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\DocumentProcessing\Client;
use Survos\DocumentProcessingBundle\SurvosDocumentProcessingBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\MockHttpClient;

final class BundleTest extends TestCase
{
    public function testKernelBoots(): void
    {
        $kernel = new DocumentKernel('test', true);
        try {
            $kernel->boot();
            self::assertSame('local', $kernel->getContainer()->get('test.document_client')->name);
        } finally {
            $kernel->shutdown();
            (new \Symfony\Component\Filesystem\Filesystem())->remove($kernel->getCacheDir());
        }
    }

    public function testNamedClientsCompileAndResolve(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.debug', true);
        $container->register('http_client', MockHttpClient::class);
        $bundle = new SurvosDocumentProcessingBundle();
        $extension = $bundle->getContainerExtension();
        $extension->load([['default_client' => 'local', 'clients' => [
            'local' => ['api_key' => '', 'base_uri' => 'http://127.0.0.1:5001/v1'],
            'cloud' => ['api_key' => 'test', 'base_uri' => 'https://api.mistral.ai/v1'],
        ]]], $container);
        $container->getAlias(Client::class)->setPublic(true);
        $container->getDefinition('survos_document_processing.client.cloud')->setPublic(true);
        $container->compile();
        self::assertSame('local', $container->get(Client::class)->name);
        self::assertSame('cloud', $container->get('survos_document_processing.client.cloud')->name);
    }
}

final class DocumentKernel extends \Symfony\Component\HttpKernel\Kernel
{
    use \Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
        yield new \Survos\Kit\SurvosKitBundle();
        yield new SurvosDocumentProcessingBundle();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/survos-document-kernel-'.getmypid();
    }

    public function getLogDir(): string
    {
        return $this->getCacheDir().'/log';
    }

    protected function configureContainer(\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container): void
    {
        $container->extension('framework', ['secret' => 'test', 'http_client' => []]);
        $container->extension('survos_document_processing', [
            'default_client' => 'local', 'clients' => ['local' => ['base_uri' => 'http://localhost:9999/v1']],
        ]);
        $container->services()->alias('test.document_client', Client::class)->public();
    }
}
