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

namespace OpenDxp\Bundle\DataImporterBundle\Controller;

use Exception;
use OpenDxp\Bundle\DataImporterBundle\DataSource\Loader\DataLoaderFactory;
use OpenDxp\Bundle\DataImporterBundle\DataSource\Loader\PushLoader;
use OpenDxp\Bundle\DataImporterBundle\Processing\ImportPreparationService;
use OpenDxp\Bundle\DataImporterBundle\Settings\ConfigurationPreparationService;
use OpenDxp\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class PushImportController
{
    protected function validateAuthorization(Request $request, PushLoader $loader)
    {
        if (!$request->headers->has('authorization')) {
            throw new AccessDeniedHttpException('Missing authorization');
        }

        $header = $request->headers->get('authorization');

        $token = trim((string) preg_replace('/^(?:\s+)?Bearer\s/', '', (string) $header));

        if (trim($token) !== trim($loader->getApiKey())) {
            throw new AccessDeniedHttpException('Invalid token');
        }
    }

    #[Route('/opendxp-datahub-import/{config}/push', requirements: ['config' => '[\w-]+'], methods: ['POST'])]
    public function pushAction(
        string $config,
        Request $request,
        ConfigurationPreparationService $configurationLoaderService,
        DataLoaderFactory $dataLoaderFactory,
        ImportPreparationService $importPreparationService
    ): JsonResponse {
        try {
            $configuration = $configurationLoaderService->prepareConfiguration($config, null, true);
            $loader = $dataLoaderFactory->loadDataLoader($configuration['loaderConfig']);

            if (!$loader instanceof PushLoader) {
                return new JsonResponse(['success' => false, 'message' => 'Endpoint not has no Push data source configured.'], 405);
            }

            $this->validateAuthorization($request, $loader);
            $success = $importPreparationService->prepareImport($config, false, $loader->isIgnoreNotEmptyQueue());

            if ($success) {
                return new JsonResponse(['success' => $success]);
            }

            return new JsonResponse(['success' => false, 'message' => 'Import not prepared, see application log for details.'], 405);
        } catch (Exception $e) {
            Logger::error($e);

            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
