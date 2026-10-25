<?php

declare(strict_types=1);

namespace Survos\DocumentProcessingBundle;

use Survos\DocumentProcessing\Client;
use Survos\DocumentProcessing\SerializerFactory;
use Survos\Kit\AbstractSurvosBundle;
use Survos\Kit\SurvosKitBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Serializer\SerializerInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

#[RequiredBundle(SurvosKitBundle::class)]
// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosDocumentProcessingBundle extends AbstractSurvosBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()->children()
            ->scalarNode('default_client')->isRequired()->cannotBeEmpty()->end()
            ->arrayNode('clients')->isRequired()->requiresAtLeastOneElement()->useAttributeAsKey('name')->arrayPrototype()->children()
                ->scalarNode('base_uri')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('api_key')->defaultValue('')->end()
                ->floatNode('timeout')->defaultValue(120)->min(0.1)->end()
            ->end()->end()->end()
        ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        if (!isset($config['clients'][$config['default_client']])) {
            throw new \InvalidArgumentException('default_client must name a configured document-processing client.');
        }
        $services = $container->services();
        $services->set('survos_document_processing.serializer', SerializerInterface::class)->factory([SerializerFactory::class, 'create']);
        foreach ($config['clients'] as $name => $options) {
            $services->set('survos_document_processing.client.'.$name, Client::class)->args([
                service('http_client'), service('survos_document_processing.serializer'), $name,
                $options['base_uri'], $options['api_key'], $options['timeout'],
            ]);
        }
        $services->alias(Client::class, 'survos_document_processing.client.'.$config['default_client']);
    }
}
