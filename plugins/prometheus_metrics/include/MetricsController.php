<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PrometheusMetrics;

use EventManager;
use ForgeConfig;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Admin\Homepage\NbUsersByStatusBuilder;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

final class MetricsController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var MetricsCollectorDao
     */
    private $dao;
    /**
     * @var NbUsersByStatusBuilder
     */
    private $nb_user_builder;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MetricsCollectorDao $dao,
        NbUsersByStatusBuilder $nb_user_builder,
        EventManager $event_manager,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->response_factory = $response_factory;
        $this->stream_factory   = $stream_factory;
        $this->dao              = $dao;
        $this->nb_user_builder  = $nb_user_builder;
        $this->event_manager    = $event_manager;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->response_factory->createResponse()
            ->withHeader('Content-Type', RenderTextFormat::MIME_TYPE)
            ->withBody(
                $this->stream_factory->createStream(
                    $this->getTuleapMetrics() .
                    $this->getTuleapComputedMetrics() .
                    $this->getNodeExporterMetrics()
                )
            );
    }

    private function getTuleapMetrics() : string
    {
        return Prometheus::instance()->renderText();
    }

    private function getTuleapComputedMetrics() : string
    {
        $prometheus = Prometheus::getInMemory();
        $collector  = new MetricsCollector($prometheus, $this->dao, $this->nb_user_builder, $this->event_manager);

        $collector->collect();

        return $prometheus->renderText();
    }

    private function getNodeExporterMetrics() : string
    {
        try {
            $node_exporter_url = ForgeConfig::get(Prometheus::CONFIG_PROMETHEUS_NODE_EXPORTER, '');
            if ($node_exporter_url === '') {
                return '';
            }
            $request_factory = HTTPFactoryBuilder::requestFactory();
            $http_client     = HttpClientFactory::createClientForInternalTuleapUse();
            $request         = $request_factory->createRequest('GET', $node_exporter_url);
            $response        = $http_client->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                return '';
            }
            return (string) $response->getBody();
        } catch (\Exception $exception) {
            return '';
        }
    }
}
