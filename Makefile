all:
	composer run-script qa-all --timeout=0

all-extended:
	composer run-script qa-all-extended --timeout=0

ci:
	composer run-script qa-ci --timeout=0

ci-extended:
	composer run-script qa-ci-extended --timeout=0

ci-windows:
	composer run-script qa-ci-windows --timeout=0

contrib:
	composer run-script qa-contrib --timeout=0

cs:
	composer cs

cs-fix:
	composer cs-fix

infection:
	composer infection

unit:
	composer run-script unit --timeout=0

stan:
	composer run-script stan --timeout=0

unit-coverage:
	composer run-script unit-coverage --timeout=0

ci-coverage:
	composer ci-coverage

make qa-unit:
	./vendor/bin/phpunit --colors=always -c ./phpunit.xml.dist --coverage-text

make qa-mutation:
	./vendor/bin/infection --ansi --min-msi=100 --min-covered-msi=100 --threads=$(nproc)

make qa-lint:
	./vendor/bin/parallel-lint --exclude vendor .

make qa-phpstan:
	./vendor/bin/phpstan analyse src tests --level max --ansi -c ./phpstan.neon

make qa-cs:
	./vendor/bin/php-cs-fixer fix --config=.php_cs --ansi --dry-run --diff --verbose --allow-risky=yes --show-progress=estimating
