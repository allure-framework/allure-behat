<?php

namespace Allure\Behat\Exception;

interface ArtifactExceptionInterface
{

  /**
   * UIExceptionInterface constructor.
   * @param $message
   * @param \Behat\Mink\Driver\CoreDriver $driver
   * @param \Exception $previous
   */
  public function __construct($message, \Behat\Mink\Driver\CoreDriver $driver, \Exception $previous = null);

  /**
   * @return string|void
   */
  public function getUrl();

  /**
   * @return string|void
   */
  public function getScreenPath();

  /**
   * @return string|void
   */
  public function getHtmlPath();
}
