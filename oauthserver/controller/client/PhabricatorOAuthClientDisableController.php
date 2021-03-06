<?php

final class PhabricatorOAuthClientDisableController
  extends PhabricatorOAuthClientController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $client = id(new PhabricatorOAuthServerClientQuery())
      ->setViewer($viewer)
      ->withIDs(array($request->getURIData('id')))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$client) {
      return new Aphront404Response();
    }

    $done_uri = $client->getViewURI();
    $is_disable = !$client->getIsDisabled();

    if ($request->isFormPost()) {
      $xactions = array();

      $xactions[] = id(new PhabricatorOAuthServerTransaction())
        ->setTransactionType(PhabricatorOAuthServerTransaction::TYPE_DISABLED)
        ->setNewValue((int)$is_disable);

      $editor = id(new PhabricatorOAuthServerEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($client, $xactions);

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    if ($is_disable) {
      $title = pht('Disable OAuth Application');
      $body = pht(
        'Really disable the %s OAuth application? Users will no longer be '.
        'able to authenticate against it, nor access this server using '.
        'tokens generated by this application.',
        phutil_tag('strong', array(), $client->getName()));
      $button = pht('Disable Application');
    } else {
      $title = pht('Enable OAuth Application');
      $body = pht(
        'Really enable the %s OAuth application? Users will be able to '.
        'authenticate against it, and existing tokens will become usable '.
        'again.',
        phutil_tag('strong', array(), $client->getName()));
      $button = pht('Enable Application');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->appendParagraph($body)
      ->addCancelButton($done_uri)
      ->addSubmitButton($button);
  }

}
