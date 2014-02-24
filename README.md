Symfony autowiring bundle
======================

A simple library that provides autowiring for the Symfony2 Dependency Injection (DI) container.

This bundle **supports constructor autowiring only**, see http://springindepth.com/book/in-depth-ioc-autowiring.html for description.

**Like this Bundle**? Send me your feedback or just "thank you" message to jirkakoutny@gmail.com.

Installation
------------

1) Add __kutny/autowiring-bundle__ to your composer.json.

~~~~~ json
"require": {
    "kutny/autowiring-bundle": "dev-master"
}
~~~~~

2) Add KutnyAutowiringBundle to your application kernel

~~~~~ php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Kutny\AutowiringBundle\KutnyAutowiringBundle(),
        // ...
    );
}
~~~~~

Configuration
-------------

In most cases this bundle **does not require any configuration**. However if your app fails to start after installing this bundle giving you Kutny\AutowiringBundle\Compiler\CannotResolveParameterException, you may need to remove some services from autowiring. See example bellow:

**Example 1**:

You are receiving Kutny\AutowiringBundle\Compiler\CannotResolveParameterException with message like "Class Thrace\FormBundle\Form\Type\Select2Type (service **thrace_form.form.type.select2**), parameter $widget".

The problem is that Thrace\FormBundle\Form\Type\Select2Type service definition does not contain explicit $widget argument definition. It is very likely that the Thrace\FormBundle developer just forgot to define the $widget argument.
KutnyAutowiringBundle expects all services to have all arguments defined (or have default values). As a result we have to disable autowiring for the thrace_form.form.type.select2 service by adding it (as a regular expression) among ignored_services:

~~~~~ yml

kutny_autowiring:
    ignored_services: ['thrace_form\.form\.type\.select2']

~~~~~

If you run into problems with more services from the Thrace\FormBundle bundle (thrace_form.form.type.select2, thrace_form.form.type.recaptcha, ...), you can easily add the whole "service namespace" to ignored_services using the following reqular expression:

~~~~~ yml

kutny_autowiring:
    ignored_services: ['thrace_form\.form\.type.*']

~~~~~

Example 1: Simple controller autowiring
-----------------------------------------

Sample controller with service autowiring (controller itself is also defined as service):

~~~~~ php
namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Facade\ProductsFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route(service="controller.products_controller")
 */
class ProductsController
{

  private $productsFacade;

    public function __construct(ProductsFacade $productsFacade)
    {
        $this->productsFacade = $productsFacade;
    }

    /**
     * @Route("/", name="route.products")
     * @Template()
     */
    public function productsAction()
    {
        return array(
            'products' => $this->productsFacade->getProducts()
        );
    }

}
~~~~~

Services configuration in app/config.yml:

~~~~~ yml

services:
    controller.products_controller:
        class: Acme\DemoBundle\Controller\ProductsController

    facade.products_facade:
        class: Acme\DemoBundle\Facade\ProductsFacade
~~~~~ 

Example 2: Partial manual wiring
-----------------------------------------

In the following example, I've added:

* __$productsPerPageLimit__ config option to ProductsController (must be wired manually)
* ProductsRepository manually wired with Doctrine2 EntityManager

~~~~~ php
<?php

namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Facade\ProductsFacade;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route(service="controller.products_controller")
 */
class ProductsController
{

    private $productsPerPageLimit;
    private $productsFacade;

    public function __construct($productsPerPageLimit, ProductsFacade $productsFacade)
    {
        $this->productsPerPageLimit = $productsPerPageLimit;
        $this->productsFacade = $productsFacade;
    }

    /**
     * @Route("/", name="route.products")
     * @Template()
     */
    public function productsAction()
    {
        return array(
            'products' => $this->productsFacade->getProducts($this->productsPerPageLimit)
        );
    }

}
~~~~~ 

~~~~~ php
<?php

namespace Acme\DemoBundle\Facade;

use Acme\DemoBundle\Repository\ProductsRepository;

class ProductsFacade
{
    private $productsRepository;

	public function __construct(ProductsRepository $productsRepository) {
		$this->productsRepository = $productsRepository;
	}

	public function getProducts($productsPerPageLimit) {
		return $this->productsRepository->getProducts($productsPerPageLimit);
	}

}
~~~~~ 

~~~~~ php
<?php

namespace Acme\DemoBundle\Repository;

use Doctrine\ORM\EntityManager;

class ProductsRepository
{
    private $entityManager;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function getProducts($productsPerPageLimit) {
        $query = $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from('AcmeDemoBundle:Product', 'p')
            ->setMaxResults($productsPerPageLimit)
            ->getQuery();

        return $query->getResult();
	}

}
~~~~~ 

**Services config**:

~~~~~ yml

services:
    controller.products_controller:
        class: Acme\DemoBundle\Controller\ProductsController
        arguments: [10]

    facade.products_facade:
        class: Acme\DemoBundle\Facade\ProductsFacade

    repository.products_repository:
        class: Acme\DemoBundle\Repository\ProductsRepository
        arguments: [@doctrine.orm.default_entity_manager]
~~~~~

Autowiring bundle will autowire some services by their type for the Symfony DI Container to work with the following configuration:

~~~~~ yml

services:
    controller.products_controller:
        class: Acme\DemoBundle\Controller\ProductsController
        arguments: [10, @facade.products_facade]

    facade.products_facade:
        class: Acme\DemoBundle\Facade\ProductsFacade
        arguments: [@repository.products_repository]

    repository.products_repository:
        class: Acme\DemoBundle\Repository\ProductsRepository
        arguments: [@doctrine.orm.default_entity_manager]
~~~~~


License
=======

https://github.com/kutny/autowiring-bundle/blob/master/LICENSE

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/kutny/autowiring-bundle/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

