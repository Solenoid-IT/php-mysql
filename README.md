# php-mysql
MySQL client library

## Docker (MySQL 8.0 for tests)

Start MySQL 8.0:

```bash
docker compose up -d
```

Shortcut:

```bash
./start
```

Stop and remove container, network and volumes:

```bash
docker compose down
```

Shortcut (stop only):

```bash
./stop
```

Restart MySQL:

```bash
./restart
```

Open MySQL shell:

```bash
./shell
```

Follow MySQL logs:

```bash
./docker/logs
```

Connection used by tests:

- Host: `127.0.0.1`
- Port: `3306`
- User: `user`
- Password: `pass`
- Database: `db`
