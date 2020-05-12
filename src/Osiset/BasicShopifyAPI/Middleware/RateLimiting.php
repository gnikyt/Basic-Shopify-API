<?php
    // /**
    //  * Handles rate limiting (if enabled).
    //  *
    //  * @return void
    //  */
    // protected function handleRateLimiting(): void
    // {
    //     if (!$this->isRateLimitingEnabled() || !$this->requestTimestamp) {
    //         return;
    //     }

    //     // Calculate in milliseconds the duration the API call took
    //     $duration = round(microtime(true) - $this->requestTimestamp, 3) * 1000;
    //     $waitTime = ($this->rateLimitCycle - $duration) + $this->rateLimitCycleBuffer;

    //     if ($waitTime > 0) {
    //         // Do the sleep for X mircoseconds (convert from milliseconds)
    //         $this->log('Rest rate limit hit');
    //         usleep($waitTime * 1000);
    //     }
    // }
