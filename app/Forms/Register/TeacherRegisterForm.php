<?php

namespace App\Forms\Register;

use App\Models\Contact;
use App\Models\PayMethod;
use App\Models\School;
use Kris\LaravelFormBuilder\Form;

class TeacherRegisterForm extends Form
{
    public function buildForm()
    {
        $this->add('school_id', 'select', [
            'label'       => 'School',
            'choices'     => School::all()->pluck('name', 'id')->toArray(),
            'empty_value' => '=== Select or Freelancer ===',
        ])
            ->add('profile_name', 'text', ['label' => '姓名*'])
            ->add('user_password', 'text', [
                'label' => '登陆密码',
                'attr'  => ['placeholder' => '默认：Teacher123'],
            ])
            ->add('telephone', 'tel', [
                'rules' => 'required|min:11',
                'label' => '手机号*',
            ])
            ->add('contact_type', 'select', [
                'label'   => '联系方式*',
                'choices' => Contact::TYPES,
                // 'selected' => 1, //'PayPal'
                'empty_value' => '=== Select ===',
            ])
            ->add('contact_number', 'text', [
                'rules' => 'required|min:4',
                'label' => '联系方式账户ID*',
            ])
            ->add('contact_remark', 'textarea', [
                'label' => '联系方式备注',
                'attr'  => ['rows' => 2, 'placeholder'=>'登陆邮箱：teacher_name@wx/skype/qq.com'],
            ])
            ->add('pmi', 'text', [
                'label'       => 'Zhumu PMI',
                'help_block'  => [
                    'text' => '可以带-或纯数字: 174-546-4410',
                    'tag'  => 'small',
                    'attr' => ['class' => 'form-text text-muted'],
                ],
            ])
            ->add('profile_sex', 'select', [
                'label'       => '性别',
                'rules'       => 'required',
                'choices'     => ['女', '男'],
                'selected'    => 0,
                'empty_value' => '=== Select ===',
            ])
            ->add('profile_birthday', 'date', ['label' => '生日'])
            ->add('pay_method', 'select', [
                'label'       => '付款方式（中教必填）',
                'choices'     => PayMethod::TYPES,
                'selected'    => 1, //'PayPal'
                'empty_value' => '=== Select ===',
            ])
            ->add('pay_number', 'text', [
                'label' => '付款账户ID（中教必填）',
            ])
            ->add('pay_remark', 'textarea', [
                'label' => '付款方式备注',
                'attr'  => ['rows' => 2],
            ])
            ->add('submit', 'submit', [
                'label' => 'Save',
                'attr'  => ['class' => 'btn btn-outline-primary'],
            ]);
    }
}
