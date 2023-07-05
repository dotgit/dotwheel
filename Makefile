.PHONY: po2php-en po2php-fr

po2php-en:
	docker run --rm -v "$$PWD":'/app' -w '/app' php php nls/po2php.php locale/en/dotwheel.po > locale/en/dotwheel.php

po2php-fr:
	docker run --rm -v "$$PWD":'/app' -w '/app' php php nls/po2php.php locale/fr/dotwheel.po > locale/fr/dotwheel.php
