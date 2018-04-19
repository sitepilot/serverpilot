name: {{ $app['name'] }}
stack: apps/custom
host: {{ $app['host'] }}
image: jwilder/whoami:latest

volumes:
  data: '/var/www/html'
  logs: '/var/www/logs'
  backup: '/var/www/backup'

database:
  host: {{ $app['database']['host'] }}
  user: {{ $app['database']['user'] }}
  password: {{ $app['database']['password'] }}
  name: {{ $app['database']['name'] }}

network:
  domains: {{ $app['name'] }}.{{ $server['appDomain'] }}
  port: 8000
  httpsOnly: false

monitor:
  domain: http://{{ $app['name'] }}.{{ $server['appDomain'] }}