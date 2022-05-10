<?php

namespace ChinhlePa\Attestation;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ChinhlePa\Attestation\Skeleton\SkeletonClass
 */
class AttestationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'attestation';
    }
}
