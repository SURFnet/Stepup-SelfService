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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class RemoteVetFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('used-before', ChoiceType::class, [
            'label' => 'ss.form.ss_remote_vet_feedback.used-before',
            'required' => true,
            'choices'   => array(
                'ss.form.ss_remote_vet_feedback.yes' => 'yes',
                'ss.form.ss_remote_vet_feedback.no' => 'no',
            ),
            'multiple' => false,
            'expanded' => true,
        ]);

        $builder->add('rating', ChoiceType::class, [
            'label' => 'ss.form.ss_remote_vet_feedback.rating',
            'required' => true,
            'choices'   => array(
                '1' => '1',
                '2' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
                '7' => '7',
                '8' => '8',
                '9' => '9',
                '10' => '10',
            ),
            'label_attr' => array('class' => 'checkbox-inline'),
            'multiple' => false,
            'expanded' => true,
        ]);

        $builder->add('rating-explanation', TextareaType::class, [
            'label' => 'ss.form.ss_remote_vet_feedback.rating-explanation',
            'required' => false,
        ]);

        $builder->add('remarks', TextareaType::class, [
            'label' => 'ss.second_factor.remote_vet.remarks',
            'required' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'ss_remote_vet_feedback';
    }
}
