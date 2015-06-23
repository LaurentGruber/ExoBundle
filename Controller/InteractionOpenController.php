<?php

namespace UJM\ExoBundle\Controller;
use Symfony\Component\Form\FormError;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use UJM\ExoBundle\Entity\InteractionOpen;
use UJM\ExoBundle\Form\InteractionOpenType;
use UJM\ExoBundle\Form\InteractionOpenHandler;

/**
 * InteractionOpen controller.
 *
 */
class InteractionOpenController extends Controller
{

    /**
     * Creates a new InteractionOpen entity.
     *
     * @access public
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction()
    {
        $interOpenSer = $this->container->get('ujm.exo_InteractionOpen');
        $interOpen  = new InteractionOpen();
        $form      = $this->createForm(
            new InteractionOpenType(
                $this->container->get('security.token_storage')->getToken()->getUser()
            ), $interOpen
        );

        $exoID = $this->container->get('request')->request->get('exercise');

        //Get the lock category
        $user = $this->container->get('security.token_storage')->getToken()->getUser()->getId();
        $Locker = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Category')->getCategoryLocker($user);
        if (empty($Locker)) {
            $catLocker = "";
        } else {
            $catLocker = $Locker[0];
        }

        $exercise = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Exercise')->find($exoID);
        $formHandler = new InteractionOpenHandler(
            $form, $this->get('request'), $this->getDoctrine()->getManager(),
            $this->container->get('ujm.exo_exercise'),
            $this->container->get('security.token_storage')->getToken()->getUser(), $exercise,
            $this->get('translator')
        );
        $openHandler = $formHandler->processAdd();
        if ($openHandler === TRUE) {
            $categoryToFind = $interOpen->getInteraction()->getQuestion()->getCategory();
            $titleToFind = $interOpen->getInteraction()->getQuestion()->getTitle();

            if ($exoID == -1) {
                return $this->redirect(
                    $this->generateUrl('ujm_question_index', array(
                        'categoryToFind' => base64_encode($categoryToFind), 'titleToFind' => base64_encode($titleToFind))
                    )
                );
            } else {
                return $this->redirect(
                    $this->generateUrl('ujm_exercise_questions', array(
                        'id' => $exoID, 'categoryToFind' => $categoryToFind, 'titleToFind' => $titleToFind)
                    )
                );
            }
        }

        if ($openHandler == 'infoDuplicateQuestion') {
            $form->addError(new FormError(
                    $this->get('translator')->trans('infoDuplicateQuestion')
                    ));
        }

        $typeOpen = $interOpenSer->getTypeOpen();
        $formWithError = $this->render(
            'UJMExoBundle:InteractionOpen:new.html.twig', array(
            'entity' => $interOpen,
            'form'   => $form->createView(),
            'exoID'  => $exoID,
            'error'  => true,
            'typeOpen' => json_encode($typeOpen)
            )
        );

        $formWithError = substr($formWithError, strrpos($formWithError, 'GMT') + 3);

        return $this->render(
            'UJMExoBundle:Question:new.html.twig', array(
            'formWithError' => $formWithError,
            'exoID'  => $exoID,
            'linkedCategory' =>  $this->container->get('ujm.exo_question')->getLinkedCategories(),
            'locker' => $catLocker
            )
        );
    }

    /**
     *
     * @access public
     *
     * Forwarded by 'UJMExoBundle:Question:edit'
     * Parameters posted :
     *     \UJM\ExoBundle\Entity\Interaction interaction
     *     integer exoID
     *     integer catID
     *     \Claroline\CoreBundle\Entity\User user
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction()
    {
        $attr = $this->get('request')->attributes;
        $openSer  = $this->container->get('ujm.exo_InteractionOpen');
        $questSer = $this->container->get('ujm.exo_question');
        $catSer = $this->container->get('ujm.exo_category');
        $em = $this->get('doctrine')->getEntityManager();

        $interactionOpen = $em->getRepository('UJMExoBundle:InteractionOpen')
                              ->getInteractionOpen($attr->get('interaction')->getId());

        $editForm = $this->createForm(
            new InteractionOpenType($attr->get('user'), $attr->get('catID')), $interactionOpen
        );

        if ($attr->get('exoID') != -1) {
            $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($attr->get('exoID'));
            $variables['_resource'] = $exercise;
        }

        $typeOpen       = $openSer->getTypeOpen();
        $linkedCategory = $questSer->getLinkedCategories();

        $variables['entity']         = $interactionOpen;
        $variables['edit_form']      = $editForm->createView();
        $variables['nbResponses']    = $openSer->getNbReponses($attr->get('interaction'));
        $variables['linkedCategory'] = $linkedCategory;
        $variables['typeOpen']       = json_encode($typeOpen);
        $variables['exoID']          = $attr->get('exoID');
        $variables['locker']         = $catSer->getLockCategory();

        if ($attr->get('exoID') != -1) {
            $exercise = $em->getRepository('UJMExoBundle:Exercise')->find($attr->get('exoID'));
            $variables['_resource'] = $exercise;
        }

        return $this->render('UJMExoBundle:InteractionOpen:edit.html.twig', $variables);
    }

    /**
     * Edits an existing InteractionOpen entity.
     *
     * @access public
     *
     * @param integer $id id of InteractionOpen
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateAction($id)
    {
        $user  = $this->container->get('security.token_storage')->getToken()->getUser();
        $exoID = $this->container->get('request')->request->get('exercise');
        $catID = -1;

        $em = $this->getDoctrine()->getManager();

        $interOpen = $em->getRepository('UJMExoBundle:InteractionOpen')->find($id);

        if (!$interOpen) {
            throw $this->createNotFoundException('Unable to find InteractionOpen entity.');
        }

        if ($user->getId() != $interOpen->getInteraction()->getQuestion()->getUser()->getId()) {
            $catID = $interOpen->getInteraction()->getQuestion()->getCategory()->getId();
        }

        $editForm = $this->createForm(
            new InteractionOpenType(
                $this->container->get('security.token_storage')->getToken()->getUser(),
                $catID
            ), $interOpen
        );

        $formHandler = new InteractionOpenHandler(
            $editForm, $this->get('request'), $this->getDoctrine()->getManager(),
            $this->container->get('ujm.exo_exercise'),
            $this->container->get('security.token_storage')->getToken()->getUser(),
            $this->get('translator')
        );

        if ($formHandler->processUpdate($interOpen)) {
           if ($exoID == -1) {

                return $this->redirect($this->generateUrl('ujm_question_index'));
           } else {

                return $this->redirect(
                    $this->generateUrl(
                        'ujm_exercise_questions',
                        array(
                            'id' => $exoID,
                        )
                    )
                );
           }
        }

        return $this->forward(
            'UJMExoBundle:Question:edit', array(
                'exoID' => $exoID,
                'id'    => $interOpen->getInteraction()->getQuestion()->getId(),
                'form'  => $editForm
            )
        );
    }

    /**
     * Deletes a InteractionOpen entity.
     *
     * @access public
     *
     * @param integer $id id of InteractionOpen
     * @param intger $pageNow for pagination, actual page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id, $pageNow)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('UJMExoBundle:InteractionOpen')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find InteractionOpen entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('ujm_question_index', array('pageNow' => $pageNow)));
    }

    /**
     * To test the open question by the teacher
     *
     * @access public
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function responseOpenAction()
    {
        $vars = array();
        $request = $this->get('request');
        $postVal = $req = $request->request->all();

        if ($postVal['exoID'] != -1) {
            $exercise = $this->getDoctrine()->getManager()->getRepository('UJMExoBundle:Exercise')->find($postVal['exoID']);
            $vars['_resource'] = $exercise;
        }

        $interSer = $this->container->get('ujm.exo_InteractionOpen');
        $res = $interSer->response($request);

        $vars['interOpen'] = $res['interOpen'];
        $vars['penalty']   = $res['penalty'];
        $vars['response']  = $res['response'];
        $vars['score']     = $res['score'];
        $vars['tempMark']  = $res['tempMark'];
        $vars['exoID']     =  $postVal['exoID'];

        return $this->render('UJMExoBundle:InteractionOpen:openOverview.html.twig', $vars);
    }
}
