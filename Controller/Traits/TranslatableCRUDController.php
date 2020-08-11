<?php

/**
 * This file is part of the "NFQ Bundles" package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nfq\AdminBundle\Controller\Traits;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TranslatableCRUDController
 * @package Nfq\AdminBundle\Controller\Traits
 */
trait TranslatableCRUDController
{
    use AbstractCrudController;

    /**
     * @var array
     */
    protected $locales;

    /**
     * Displays a form to create a new  entity.
     *
     * @Route("/new")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function newAction(Request $request)
    {
        $this->loadLocales();

        $forms = [];
        foreach ($this->locales as $locale) {
            /** @var Form $form */
            list(, $form) = $this->getCreateFormAndEntity($locale);
            $forms[$locale] = $form->createView();
        }

        return [
            'forms' => $forms,
        ];
    }

    /**
     * Creates form and entity for given locale
     *
     * @param string $locale
     * @return array<$entity, Form $form>
     */
    abstract protected function getCreateFormAndEntity($locale);

    /**
     * Creates a new entity.
     *
     * @Route("/new")
     * @Method("POST")
     * @Template()
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $this->loadLocales();

        $forms = [];
        foreach ($this->locales as $locale) {
            /** @var Form $form */
            list($entity, $form) = $this->getCreateFormAndEntity($locale);

            if ($request->isMethod('POST') && $request->request->get($form->getName())['locale'] == $locale) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $this->insertAfterCreateAction($entity);
                    return $this->redirectToIndex($request, $entity);
                }
            }

            $forms[$locale] = $form->createView();
        }

        return [
            'forms' => $forms,
        ];
    }

    /**
     * Edits an existing entity.
     *
     * @Route("/{id}/update")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function updateAction(Request $request, $id)
    {
        $this->loadLocales();

        //Correct locale for TranslatableListener is passed via event listener, so passing null here
        $baseEntity = $this->getEditableEntityForLocale($id, null);

        if (!$baseEntity) {
            throw new NotFoundHttpException('Entity was not found');
        }

        $submitLocale = null;
        if ($request->isMethod('POST')) {
            if (false !== ($result = $this->doUpdate($request, $baseEntity)) instanceof RedirectResponse) {
                return $result;
            }

            $submitLocale = $result->getData()->getLocale();
        }

        $baseEntity = clone $baseEntity;

        $forms = [];
        foreach ($this->locales as $locale) {
            $editableEntity = $this->getEditableEntityForLocale($id, $locale);
            $editableEntity->setLocale($locale);

            /**
             * @var Form $editForm
             * @var Form $deleteForm
             */
            list($editForm, $deleteForm) = $this->getEditDeleteForms(clone $editableEntity);

            //Due to referenced base entity we have to recreate edit form for every locale, because entity of submitted
            //form changes while looping other locales thus final result of the locale entity is incorrect. But then
            //we loose form errors, so here we have to re-validate the form of submitted locale
            if ($submitLocale && $submitLocale == $locale) {
                $editForm->handleRequest($request);
                $editForm->isValid();
            }

            $forms[$locale] = [
                'edit' => $editForm->createView(),
                'delete' => $deleteForm->createView(),
            ];
        }

        return [
            'forms' => $forms,
            'entity' => $baseEntity,
            'submitLocale' => $submitLocale,
        ];
    }

    /**
     * @param Request $request
     * @param $entity
     * @return Form[]|RedirectResponse
     */
    private function doUpdate(Request $request, $entity)
    {
        /**
         * @var Form $editForm
         */
        list($editForm,) = $this->getEditDeleteForms($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $this->saveAfterUpdateAction($entity);

            return $this->handleAfterSubmit($request, $editForm);
        }

        return $editForm;
    }

    /**
     * @param bool $defaultFirst
     */
    protected function loadLocales($defaultFirst = false)
    {
        $defaultLocale = $this->getParameter('locale');
        $locales = ($this->hasParameter('locales'))
            ? $this->getParameter('locales')
            : [$defaultLocale];

        if ($defaultFirst) {
            //unset default locale and set it as first element in locales array
            $defaultIdx = array_search($defaultLocale, $locales);
            unset($defaultIdx);
            array_unshift($locales, $defaultLocale);
        }

        $this->locales = $locales;
    }

    /**
     * Save entity after insert
     * @param $entity
     */
    abstract protected function insertAfterCreateAction($entity);

    /**
     * Returns ant editable entity for given locale.
     *
     * @param int $id
     * @param string $locale
     * @return object|null
     */
    abstract protected function getEditableEntityForLocale($id, $locale);

    /**
     * Returns ant editable entity for given locale.
     *
     * @param int $id
     * @return object|null
     */
    protected function getEntity($id)
    {
        $locale = $this->container->get('request_stack')->getCurrentRequest()->getLocale();

        return $this->getEditableEntityForLocale($id, $locale);
    }
}
