<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupSelfService\SelfServiceBundle\Form\Type;

use Surfnet\StepupSelfService\SelfServiceBundle\Service\RemoteVetting\Value\AttributeMatch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemoteVetAssertionMatchType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('valid', ChoiceType::class, [
            'label' => false,
            'required' => true,
            'choices'   => array(
                'ss.form.ss_remote_vet_feedback.yes' => true,
                'ss.form.ss_remote_vet_feedback.no' => false,
            ),
            'multiple' => false,
            'expanded' => true,
        ]);

        $builder->add('remarks', TextareaType::class, [
            'label'    => false,
            'required' => false,
        ]);

        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AttributeMatch::class,
            'attr' => ['class' => 'form-inline'],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'ss_remote_vet_assertion';
    }

    /**
     * @param $viewData AttributeMatch
     * @inheritDoc
     */
    public function mapDataToForms($viewData, $forms)
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof AttributeMatch) {
            throw new UnexpectedTypeException($viewData, AttributeMatch::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // Set VO values to form
        $forms['valid']->setData($viewData->isValid());
        $forms['remarks']->setData($viewData->getRemarks());
    }

    /**
     * @param $viewData AttributeMatch
     * @inheritDoc
     */
    public function mapFormsToData($forms, &$viewData)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // Keep the name from the original object
        $viewData = new AttributeMatch(
            $viewData->getLocalAttribute(),
            $viewData->getRemoteAttribute(),
            $forms['valid']->getData(),
            (string)$forms['remarks']->getData()
        );
    }
}
