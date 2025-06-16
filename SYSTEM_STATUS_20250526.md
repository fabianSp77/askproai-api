# AskProAI System Status Report
Generiert am: $(date)

## 1. Umgebungsvariablen
APP_NAME="AskProAI"
APP_ENV=production
APP_KEY=base64:SszZdhcWkqltdJYNVkEEntBHk/9TfeT4NUIOkAnZcHE=
APP_CIPHER=aes-256-cbc
APP_DEBUG=true
APP_URL=https://api.askproai.de
CALCOM_API_KEY=cal_live_e9aa2c4d18e0fd79cf4f8dddb90903da
CALCOM_EVENT_TYPE_ID=2026302
CALCOM_TEAM_SLUG=askproai
CALCOM_BASE_URL=https://api.cal.com/v1
CALCOM_WEBHOOK_SECRET=6846aed4d55f6f3df70c40781e02d964aae34147f72763e1ccedd726e66dfff7
RETELL_BASE=https://api.retell.ai
RETELL_TOKEN=key_6ff998ba48e842092e04a5455d19
RETELL_WEBHOOK_SECRET=key_6ff998ba48e842092e04a5455d19
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=askproai_db
DB_USERNAME=askproai_user
DB_PASSWORD=***
CALCOM_CLIENT_ID=
CALCOM_CLIENT_SECRET=
CALCOM_REDIRECT_URI=https://api.askproai.de/api/calcom/oauth/callback

## 2. Laravel Status

  Environment ......................................................................................................................................  
  Application Name ........................................................................................................................ AskProAI  
  Laravel Version .......................................................................................................................... 11.44.7  
  PHP Version ............................................................................................................................... 8.2.28  
  Composer Version ........................................................................................................................... 2.5.5  
  Environment ........................................................................................................................... production  
  Debug Mode ............................................................................................................................... ENABLED  
  URL .............................................................................................................................. api.askproai.de  
  Maintenance Mode ............................................................................................................................. OFF  
  Timezone ........................................................................................................................... Europe/Berlin  
  Locale ........................................................................................................................................ de  

  Cache ............................................................................................................................................  
  Config ................................................................................................................................ NOT CACHED  
  Events ................................................................................................................................ NOT CACHED  
  Routes ................................................................................................................................ NOT CACHED  
  Views ..................................................................................................................................... CACHED  

  Drivers ..........................................................................................................................................  
  Broadcasting ................................................................................................................................ null  
  Cache ................................................................................................................................... database  
  Database ................................................................................................................................... mysql  
  Logs .............................................................................................................................. stack / single  
  Mail ........................................................................................................................................ smtp  
  Queue ....................................................................................................................................... sync  
  Session ..................................................................................................................................... file  

  Shield ...........................................................................................................................................  
  Auth Provider ......................................................................................................... App\Models\User|CONFIGURED  
  Tenancy ................................................................................................................................. DISABLED  
  Tenant Model .....................................................................................................................................  
  Translations ....................................................................................................................... NOT PUBLISHED  
  Version .................................................................................................................................... 3.3.6  
  Views .............................................................................................................................. NOT PUBLISHED  

  Filament .........................................................................................................................................  
  Blade Icons ........................................................................................................................... NOT CACHED  
  Packages ......................................................................................... filament, forms, notifications, support, tables  
  Panel Components .......................................................................................................................... CACHED  
  Version .................................................................................................................................. v3.3.14  
  Views .............................................................................................................................. NOT PUBLISHED  

  Livewire .........................................................................................................................................  
  Livewire .................................................................................................................................. v3.6.3  

  Spatie Permissions ...............................................................................................................................  
  Features Enabled ......................................................................................................................... Default  
  Version ................................................................................................................................... 6.18.0  


## 3. Verfügbare API-Routen

  GET|HEAD   api/calcom/webhook ........................................................................................ CalcomWebhookController@ping
  POST       api/calcom/webhook ...................................................................................... CalcomWebhookController@handle
  POST       api/retell/webhook .................................................................................... RetellWebhookController@__invoke
  GET|HEAD   horizon/api/batches ............................................. horizon.jobs-batches.index › Laravel\Horizon › BatchesController@index
  POST       horizon/api/batches/retry/{id} .................................. horizon.jobs-batches.retry › Laravel\Horizon › BatchesController@retry
  GET|HEAD   horizon/api/batches/{id} .......................................... horizon.jobs-batches.show › Laravel\Horizon › BatchesController@show
  GET|HEAD   horizon/api/jobs/completed .............................. horizon.completed-jobs.index › Laravel\Horizon › CompletedJobsController@index
  GET|HEAD   horizon/api/jobs/failed ....................................... horizon.failed-jobs.index › Laravel\Horizon › FailedJobsController@index
  GET|HEAD   horizon/api/jobs/failed/{id} .................................... horizon.failed-jobs.show › Laravel\Horizon › FailedJobsController@show
  GET|HEAD   horizon/api/jobs/pending .................................... horizon.pending-jobs.index › Laravel\Horizon › PendingJobsController@index
  POST       horizon/api/jobs/retry/{id} .......................................... horizon.retry-jobs.show › Laravel\Horizon › RetryController@store
  GET|HEAD   horizon/api/jobs/silenced ................................. horizon.silenced-jobs.index › Laravel\Horizon › SilencedJobsController@index
  GET|HEAD   horizon/api/jobs/{id} ........................................................ horizon.jobs.show › Laravel\Horizon › JobsController@show
  GET|HEAD   horizon/api/masters ......................................... horizon.masters.index › Laravel\Horizon › MasterSupervisorController@index
  GET|HEAD   horizon/api/metrics/jobs ..................................... horizon.jobs-metrics.index › Laravel\Horizon › JobMetricsController@index
  GET|HEAD   horizon/api/metrics/jobs/{id} .................................. horizon.jobs-metrics.show › Laravel\Horizon › JobMetricsController@show
  GET|HEAD   horizon/api/metrics/queues ............................... horizon.queues-metrics.index › Laravel\Horizon › QueueMetricsController@index
  GET|HEAD   horizon/api/metrics/queues/{id} ............................ horizon.queues-metrics.show › Laravel\Horizon › QueueMetricsController@show
  GET|HEAD   horizon/api/monitoring ......................................... horizon.monitoring.index › Laravel\Horizon › MonitoringController@index
  POST       horizon/api/monitoring ......................................... horizon.monitoring.store › Laravel\Horizon › MonitoringController@store
  GET|HEAD   horizon/api/monitoring/{tag} ......................... horizon.monitoring-tag.paginate › Laravel\Horizon › MonitoringController@paginate
  DELETE     horizon/api/monitoring/{tag} ........................... horizon.monitoring-tag.destroy › Laravel\Horizon › MonitoringController@destroy
  GET|HEAD   horizon/api/stats ............................................... horizon.stats.index › Laravel\Horizon › DashboardStatsController@index
  GET|HEAD   horizon/api/workload ............................................... horizon.workload.index › Laravel\Horizon › WorkloadController@index

                                                                                                                                  Showing [24] routes

