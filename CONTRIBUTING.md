# Contributing to DynamoImage

First off, thanks for taking the time to contribute!

For local package development use [Docker](https://www.docker.com/products/docker-desktop):

Build Test container
```
git clone https://github.com/Ouxsoft/DynamoImage.git
cd DynamoImage
docker build --target test --tag dynamoimage:latest -f Dockerfile .
docker run -it --mount type=bind,source="$(pwd)"/,target=/application/ dynamoimage:latest composer install
```

Run Automated Unit Tests using local volume
```
docker run -it --mount type=bind,source="$(pwd)"/,target=/application/ dynamoimage:latest composer test
```

Run Automated Benchmark Tests using local volume
```
docker run -it --mount type=bind,source="$(pwd)"/,target=/application/ dynamoimage:latest ./vendor/bin/phpbench run tests/src/Benchmark --report=default
```

Run Bin using local volume
```
docker run -it --mount type=bind,source="$(pwd)"/,target=/application/ dynamoimage:latest bin/dynamoimage 1d10+4*2 0
```

Start test server available at [http://localhost/](http://localhost/test.html)
```
docker run -it -p 80:80 --mount type=bind,source="$(pwd)"/,target=/application dynamoimage:latest bash -c 'cd public && php -S 0.0.0.0:80'
```

Rebuild Docs
```
docker build --target docs --tag dynamoimage:docs-latest -f Dockerfile .
docker run -it --mount type=bind,source="$(pwd)"/docs,target=/app/docs dynamoimage:docs-latest bash -c "doxygen Doxyfile && doxyphp2sphinx Ouxsoft::dynamoimage"
```
