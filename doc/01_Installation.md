# Installation
## Required Bundles
This bundle depends on OpenDXP [Datahub bundle](https://github.com/open-dxp/data-hub-bundle). This needs to be installed first.

## Installation Process
### For OpenDXP >= 10.5
To install OpenDXP Data Importer for OpenDXP 10.5 or higher, follow the three steps below:

1. Install the required dependencies:
```bash
composer require open-dxp/data-importer-bundle
```

2. Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```php
use OpenDxp\Bundle\DataImporterBundle\OpenDxpDataImporterBundle;
// ...

return [
    // ...
    // make sure OpenDxpDataHubBundle is added before to that list
    // ...
    OpenDxpDataImporterBundle::class => ['all' => true],
    // ...
];
```

3. Install the bundle:

```bash
bin/console opendxp:bundle:install OpenDxpDataImporterBundle
```

> Make sure the Datahub bundle's priority is higher than the Data Importer bundle's.
> 
> This can be specified as a parameter during bundle enablement
 

## Bundle Configuration

### Import Execution
The imports are executed asynchronously in the background. The processing can be done via executing commands on a regular 
basis or by utilizing symfony messenger. Either of the two needs to be configured. 

#### Command Based
For command based importing, following commands need to be executed on regular base. The actual interval depends
on use cases and system environment. 

```bash
# Process import queue items that can be executed in parallel
*/5 * * * * php /home/project/www/bin/console datahub:data-importer:process-queue-parallel --processes=5
# Process import queue items that need to be executed sequentially 
*/5 * * * * php /home/project/www/bin/console datahub:data-importer:process-queue-sequential 
```

See [Import Execution Details](04_Import_Execution_Details.md) for more information about sequential and parallel execution.


#### Symfony Messenger Based
For symfony messenger based importing, at least following configuration needs to be done in symfony configuration: 
```yml 
opendxp_data_importer:
    messenger_queue_processing:
        activated: true
```

If activated, the processing is kicked off automatically as soon as an import is prepared. 

In addition to that, following settings are available. They all have meaningful default values though: 
- `worker_count_parallel`: Count of maximum parallel worker messages for parallel imports.
- `worker_item_count`: Count of items imported per worker message.
- `worker_count_lifetime`: Lifetime of tmp store entry for current worker count entry. After lifetime, the value will be cleared.

Messages are dispatched via `opendxp_data_import` transport. So make sure, you have
workers processing this transport when activating the messenger based queue processing.


### Cron Execution
Import configuration can be setup to be executed on regular base by defining a cron definition. To make sure, the cron 
definitions are checked on regular base, following command needs to be executed on regular base. The actual interval depends 
on use cases and system environment; the shorter, the more accurate the import execution will take place. 
```bash 
# Check cron configurations and execute necessary import definitions
* * * * * php /home/project/www/bin/console datahub:data-importer:execute-cron
```
