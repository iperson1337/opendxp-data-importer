<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\DataImporterBundle;

use League\FlysystemBundle\FlysystemBundle;
use OpenDxp\Bundle\AdminBundle\OpenDxpAdminBundle;
use OpenDxp\Bundle\ApplicationLoggerBundle\OpenDxpApplicationLoggerBundle;
use OpenDxp\Bundle\DataHubBundle\OpenDxpDataHubBundle;
use OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass\CleanupStrategyConfigurationFactoryPass;
use OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass\InterpreterConfigurationFactoryPass;
use OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass\LoaderConfigurationFactoryPass;
use OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass\MappingConfigurationFactoryPass;
use OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass\MultiAttributeConfigurationFactoryPass;
use OpenDxp\Bundle\DataImporterBundle\DependencyInjection\CompilerPass\ResolverConfigurationFactoryPass;
use OpenDxp\Bundle\DataImporterBundle\DependencyInjection\OpenDxpDataImporterExtension;
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\Installer\InstallerInterface;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;
use OpenDxp\Extension\Bundle\Traits\PackageVersionTrait;
use OpenDxp\HttpKernel\Bundle\DependentBundleInterface;
use OpenDxp\HttpKernel\BundleCollection\BundleCollection;
use Override;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class OpenDxpDataImporterBundle extends AbstractOpenDxpBundle implements DependentBundleInterface, OpenDxpBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;

    const LOGGER_COMPONENT_PREFIX = 'DATA-IMPORTER ';

    protected function getComposerPackageName(): string
    {
        return 'iperson1337/opendxp-data-importer';
    }

    #[Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if ($this->extension === null) {
            $this->extension = new OpenDxpDataImporterExtension();
        }

        return $this->extension;
    }

    /**
     * @return string[]
     */
    public function getCssPaths(): array
    {
        return [
            '/bundles/opendxpdataimporter/css/icons.css',
        ];
    }

    /**
     * @return string[]
     */
    public function getJsPaths(): array
    {
        return [
            '/bundles/opendxpdataimporter/js/opendxp/helper/ext_extensions.js',
            '/bundles/opendxpdataimporter/js/opendxp/helper/abstractOptionType.js',
            '/bundles/opendxpdataimporter/js/opendxp/adapter/dataImporterDataObject.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/configEvents.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/configItemDataObject.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/loader/sftp.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/loader/http.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/loader/asset.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/loader/upload.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/loader/push.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/loader/sql.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/interpreter/csv.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/interpreter/json.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/interpreter/xlsx.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/interpreter/xml.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/interpreter/sql.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/cleanup/unpublish.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/cleanup/delete.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/importSettings.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/importPreview.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/load/id.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/load/path.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/load/attribute.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/load/notLoad.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/load/multiAttribute.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/location/staticPath.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/location/findParent.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/location/findOrCreateFolder.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/location/findParentAndCreateFolder.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/location/noChange.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/location/doNotCreate.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/publish/alwaysPublish.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/publish/attributeBased.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/publish/noChangePublishNew.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/resolver/publish/noChangeUnpublishNew.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/mappingConfiguration.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/mappingConfigurationItem.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/transformationResultHandler.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/datatarget/direct.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/datatarget/manyToManyRelation.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/datatarget/classificationstore.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/datatarget/classificationstoreBatch.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/tools/classificationStoreKeySearchWindow.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/abstractOperator.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/trim.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/numeric.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/asArray.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/asCountries.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/asGeopoint.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/asGeobounds.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/asGeopolygon.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/asGeopolyline.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/asColor.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/explode.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/combine.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/numericRange.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/htmlDecode.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/quantityValue.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/quantityValueArray.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/inputQuantityValue.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/inputQuantityValueArray.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/boolean.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/date.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/time.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/importAsset.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/loadAsset.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/gallery.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/imageAdvanced.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/loadDataObject.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/objectField.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/reduceArrayKeyValuePairs.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/flattenArray.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/staticText.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/conditionalConversion.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/mapping/operator/stringReplace.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/execution.js',
            '/bundles/opendxpdataimporter/js/opendxp/configuration/components/logTab.js',
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new MappingConfigurationFactoryPass())
            ->addCompilerPass(new MultiAttributeConfigurationFactoryPass())
            ->addCompilerPass(new ResolverConfigurationFactoryPass())
            ->addCompilerPass(new LoaderConfigurationFactoryPass())
            ->addCompilerPass(new InterpreterConfigurationFactoryPass())
            ->addCompilerPass(new CleanupStrategyConfigurationFactoryPass())
        ;
    }

    public static function registerDependentBundles(BundleCollection $collection): void
    {
        $collection->addBundle(OpenDxpDataHubBundle::class, 20);
        $collection->addBundle(new FlysystemBundle());
        $collection->addBundle(new OpenDxpAdminBundle(), 60);

        $collection->addBundle(OpenDxpApplicationLoggerBundle::class, 10);
    }

    public function getInstaller(): ?InstallerInterface
    {
        return $this->container->get(Installer::class);
    }
}
