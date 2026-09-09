#!/bin/bash
set -e

# Wait for PostgreSQL to become ready
if [ -n "$PGHOST" ]; then
  echo "Waiting for PostgreSQL ($PGHOST:$PGPORT)..."
  until PGPASSWORD=$PGPASSWORD psql -h "$PGHOST" -p "${PGPORT:-5432}" -U "$PGUSER" -d "$PGDATABASE" -c '\q' 2>/dev/null; do
    echo "Postgres is unavailable - sleeping 2s"
    sleep 2
  done

  echo "PostgreSQL is up. Checking if database initialization is needed..."
  
  # Check if RosarioSIS tables already exist
  TABLE_COUNT=$(PGPASSWORD=$PGPASSWORD psql -h "$PGHOST" -p "${PGPORT:-5432}" -U "$PGUSER" -d "$PGDATABASE" -t -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';" | tr -d '[:space:]')

  if [ "$TABLE_COUNT" -eq "0" ]; then
    echo "Empty database detected. Importing rosariosis.sql..."
    if [ -f /var/www/html/rosariosis.sql ]; then
      PGPASSWORD=$PGPASSWORD psql -h "$PGHOST" -p "${PGPORT:-5432}" -U "$PGUSER" -d "$PGDATABASE" -f /var/www/html/rosariosis.sql
      echo "Database initialized successfully!"
    else
      echo "Warning: rosariosis.sql not found at /var/www/html/rosariosis.sql"
    fi
  else
    echo "Database already contains $TABLE_COUNT tables. Skipping import."
  fi
fi

exec "$@"
