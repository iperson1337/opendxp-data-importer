<?php

declare(strict_types=1);

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
use OpenDxp\Controller\UserAwareController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/opendxpdataimporter')]
class ConnectionController extends UserAwareController
{
    /**
     * @throws Exception
     */
    #[Route('/connections', name: 'opendxp_dataimporter_connections', methods: ['GET'])]
    public function connectionAction(): JsonResponse
    {
        $connections = $this->getParameter('doctrine.connections');

        $mappedConnections = array_map(fn ($key, $value): array => [
            'name' => $key,
            'value' => $value,
        ], array_keys($connections), $connections);

        return $this->json($mappedConnections);
    }
}
