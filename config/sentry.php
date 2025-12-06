<?php

return array(
    'dsn' => 'https://773a3924ff46481b903d0674901430cf@o575235.ingest.sentry.io/5727194',
    'traces_sample_rate' => 1.0,
    'traces_sampler' => function (\Sentry\Tracing\SamplingContext $context): float {
        return 1.0;
    }
    // capture release as git sha
    // 'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),
);
