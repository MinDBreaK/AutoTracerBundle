# AutoTracerBundle
![https://shepherd.dev/github/MinDBreaK/AutoTracerBundle](https://shepherd.dev/github/MinDBreaK/AutoTracerBundle/coverage.svg)

This bundle aims  at tracing automatically requests & events in a Symfony application.

## Requirements
* PHP 8
* Symfony 5
* Doctrine (Will be made optional later)
* Jaeger

## Installation

You will need to change your `minimum-stability`.
```
composer require mindbreak/auto-tracer-bundle
```

Then, enable the bundle in `bundles.php`

```php
return [
    //...
    Mindbreak\SymfonyAutoTracer\SymfonyAutoTracerBundle::class => ['all' => true],
];
```

Create the `config/packages/mindbreak_auto_tracer.yaml`

```yaml
mindbreak_auto_tracer:
    serverName: api-server #The server name that should appear in Jaeger. Usually your app name
    agentHostPort: jaeger:5775 # Or "%env(JAEGER_HOST)%" and declare the env var
    
    doctrine:
        traceArgs: true # If you want to log args, but will hit a bit perfs
```

