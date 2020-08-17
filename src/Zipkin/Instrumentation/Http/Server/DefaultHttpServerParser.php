<?php

/**
 * Copyright 2020 OpenZipkin Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Zipkin\Instrumentation\Http\Server;

use Zipkin\Tags;
use Zipkin\SpanCustomizer;
use Zipkin\Propagation\TraceContext;
use Zipkin\Instrumentation\Http\Server\HttpServerParser;

/**
 * DefaultParser contains the basic logic for turning request/response information
 * into span name and tags. Implementors can use this as a base parser to reduce
 * boilerplate.
 */
class DefaultHttpServerParser implements HttpServerParser
{
    /**
     * spanName returns an appropiate span name based on the request,
     * usually the HTTP method is enough (e.g GET or POST) but ideally
     * the http.route is desired (e.g. /user/{user_id}).
     */
    protected function spanName(Request $request): string
    {
        return $request->getMethod()
            . ($request->getRoute() === null ? '' : ' ' . $request->getRoute());
    }

    /**
     * {@inhertidoc}
     */
    public function request(Request $request, TraceContext $context, SpanCustomizer $span): void
    {
        $span->setName($this->spanName($request));
        $span->tag(Tags\HTTP_METHOD, $request->getMethod());
        $span->tag(Tags\HTTP_PATH, $request->getPath() ?: '/');
    }

    /**
     * {@inhertidoc}
     */
    public function response(Response $response, TraceContext $context, SpanCustomizer $span): void
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode > 299) {
            $span->tag(Tags\HTTP_STATUS_CODE, (string) $statusCode);
        }

        if ($statusCode > 399) {
            $span->tag(Tags\ERROR, (string) $statusCode);
        }
    }
}