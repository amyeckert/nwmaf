<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport;

use DeliciousBrains\WPMDB\Common\Exceptions\UnknownTransportMethod;
use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\HTTP\CURLFileTransport;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\HTTP\FileInBodyTransport;
use DeliciousBrains\WPMDB\WPMDBDI;

class TransportFactory
{
    /**
     * Creates a transport method instance.
     *
     * @param string $transport
     *
     * @return TransportInterface
     */
    public static function create($transport)
    {
        $container = WPMDBDI::getInstance();

        switch ($transport) {
            case CURLFileTransport::class:
                $util = $container->get(Util::class);

                return new CURLFileTransport($util);
            case FileInBodyTransport::class:
                $remote_post = $container->get(RemotePost::class);

                return new FileInBodyTransport($remote_post);
            default:
                throw new UnknownTransportMethod(
                    sprintf(
                        __('Unknown transport method %s passed to factory.', 'wp-migrate-db'),
                        $transport
                    )
                );
        }
    }
}
