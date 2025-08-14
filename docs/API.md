# API Overview

## Authentication
```
Authorization: Bearer <token>
```

## Required header for Cal.com v2
```
cal-api-version: 2
```

## Database restore example
```
gunzip -c /path/to/dump.sql.gz | mysql -h \$DB\_HOST -P \$DB\_PORT -u \$DB\_USERNAME -p\$DB\_PASSWORD \$DB\_DATABASE
```
