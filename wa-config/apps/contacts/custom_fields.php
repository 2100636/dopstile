<?php
return array (
  0 => 
  waContactNameField::__set_state(array(
     'id' => 'name',
     'options' => 
    array (
      'max_length' => 150,
      'storage' => 'info',
      'fconstructor' => 'hidden',
      'required' => false,
      'subfields_order' => 
      array (
        0 => 'firstname',
        1 => 'middlename',
        2 => 'lastname',
      ),
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Хотя бы одно из этих полей должно быть заполнено',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 150 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => true,
          'max_length' => 150,
          'storage' => 'info',
          'fconstructor' => 'hidden',
          'subfields_order' => 
          array (
            0 => 'firstname',
            1 => 'middlename',
            2 => 'lastname',
          ),
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'allow_self_edit' => false,
      'unique' => false,
    ),
     'name' => 
    array (
      'en_US' => 'Name',
    ),
     '_type' => 'waContactNameField',
  )),
  1 => 
  waContactStringField::__set_state(array(
     'id' => 'title',
     'options' => 
    array (
      'max_length' => 50,
      'storage' => 'info',
      'type' => 'NameSubfield',
      'fconstructor' => 'fixed',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 50 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'max_length' => 50,
          'storage' => 'info',
          'type' => 'NameSubfield',
          'fconstructor' => 'fixed',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
    ),
     'name' => 
    array (
      'en_US' => 'Title',
    ),
     '_type' => 'waContactStringField',
  )),
  2 => 
  waContactStringField::__set_state(array(
     'id' => 'firstname',
     'options' => 
    array (
      'max_length' => 50,
      'storage' => 'info',
      'type' => 'NameSubfield',
      'fconstructor' => 'fixed',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 50 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'max_length' => 50,
          'storage' => 'info',
          'type' => 'NameSubfield',
          'fconstructor' => 'fixed',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
      'my_profile' => 2,
    ),
     'name' => 
    array (
      'en_US' => 'First name',
    ),
     '_type' => 'waContactStringField',
  )),
  3 => 
  waContactStringField::__set_state(array(
     'id' => 'middlename',
     'options' => 
    array (
      'max_length' => 50,
      'storage' => 'info',
      'type' => 'NameSubfield',
      'fconstructor' => 'fixed',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 50 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'max_length' => 50,
          'storage' => 'info',
          'type' => 'NameSubfield',
          'fconstructor' => 'fixed',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
      'my_profile' => 2,
    ),
     'name' => 
    array (
      'en_US' => 'Middle name',
    ),
     '_type' => 'waContactStringField',
  )),
  4 => 
  waContactStringField::__set_state(array(
     'id' => 'lastname',
     'options' => 
    array (
      'max_length' => 50,
      'storage' => 'info',
      'type' => 'NameSubfield',
      'fconstructor' => 'fixed',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 50 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'max_length' => 50,
          'storage' => 'info',
          'type' => 'NameSubfield',
          'fconstructor' => 'fixed',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
      'my_profile' => 2,
    ),
     'name' => 
    array (
      'en_US' => 'Last name',
    ),
     '_type' => 'waContactStringField',
  )),
  5 => 
  waContactHiddenField::__set_state(array(
     'id' => 'company_contact_id',
     'options' => 
    array (
      'storage' => 'info',
      'type' => 'Hidden',
    ),
     'name' => 
    array (
      'en_US' => '',
    ),
     '_type' => 'waContactHiddenField',
  )),
  6 => 
  waContactRadioSelectField::__set_state(array(
     'validate_range' => true,
     'id' => 'sex',
     'options' => 
    array (
      'storage' => 'info',
      'fconstructor' => 'fixed',
      'translate_options' => true,
      'options' => 
      array (
        'm' => 'Male',
        'f' => 'Female',
      ),
    ),
     'name' => 
    array (
      'en_US' => 'Gender',
    ),
     '_type' => 'waContactRadioSelectField',
  )),
  7 => 
  waContactStringField::__set_state(array(
     'id' => 'jobtitle',
     'options' => 
    array (
      'max_length' => 50,
      'storage' => 'info',
      'fconstructor' => 'fixed',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 50 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'max_length' => 50,
          'storage' => 'info',
          'fconstructor' => 'fixed',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
    ),
     'name' => 
    array (
      'en_US' => 'Job title',
    ),
     '_type' => 'waContactStringField',
  )),
  8 => 
  waContactStringField::__set_state(array(
     'id' => 'company',
     'options' => 
    array (
      'max_length' => 150,
      'storage' => 'info',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 150 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'max_length' => 150,
          'storage' => 'info',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
    ),
     'name' => 
    array (
      'en_US' => 'Company',
    ),
     '_type' => 'waContactStringField',
  )),
  9 => 
  waContactEmailField::__set_state(array(
     'id' => 'email',
     'options' => 
    array (
      'multi' => true,
      'storage' => 'email',
      'ext' => 
      array (
        'work' => 'work',
        'personal' => 'personal',
      ),
      'top' => true,
      'validators' => 
      waEmailValidator::__set_state(array(
         'options' => 
        array (
          'required' => false,
          'pattern' => '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:[a-z0-9](?:[\\-a-z0-9]*[a-z0-9])*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD',
          'multi' => true,
          'storage' => 'email',
          'ext' => 
          array (
            'work' => 'work',
            'personal' => 'personal',
          ),
          'top' => true,
        ),
         'messages' => 
        array (
          'required' => 'Это поле обязательное',
          'invalid' => 'Неверно',
          'not_match' => 'Email-адрес введен неправильно',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waEmailValidator',
      )),
      'formats' => 
      array (
        'js' => 
        waContactEmailListFormatter::__set_state(array(
           '_type' => 'waContactEmailListFormatter',
           'options' => NULL,
        )),
        'top' => 
        waContactEmailTopFormatter::__set_state(array(
           '_type' => 'waContactEmailTopFormatter',
           'options' => NULL,
        )),
        'html' => 
        waContactEmailTopFormatter::__set_state(array(
           '_type' => 'waContactEmailTopFormatter',
           'options' => NULL,
        )),
      ),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
      'my_profile' => 2,
    ),
     'name' => 
    array (
      'en_US' => 'Email',
    ),
     '_type' => 'waContactEmailField',
  )),
  10 => 
  waContactBirthdayField::__set_state(array(
     'id' => 'birthday',
     'options' => 
    array (
      'storage' => 'info',
      'prefix' => 'birth',
      'formats' => 
      array (
        'html' => 
        waContactBirthdayLocalFormatter::__set_state(array(
           '_type' => 'waContactBirthdayLocalFormatter',
           'options' => 
          array (
            'prefix' => 'birth',
          ),
        )),
        'locale' => 
        waContactBirthdayLocalFormatter::__set_state(array(
           '_type' => 'waContactBirthdayLocalFormatter',
           'options' => 
          array (
            'prefix' => 'birth',
          ),
        )),
        'list' => 
        waContactBirthdayLocalFormatter::__set_state(array(
           '_type' => 'waContactBirthdayLocalFormatter',
           'options' => 
          array (
            'prefix' => 'birth',
          ),
        )),
      ),
      'validators' => 
      waDateValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Это поле обязательное',
          'invalid' => 'Неверно',
          'incorrect_date' => 'Неправильная дата',
        ),
         'options' => 
        array (
          'required' => false,
          'storage' => 'info',
          'prefix' => 'birth',
          'formats' => 
          array (
            'html' => 
            waContactBirthdayLocalFormatter::__set_state(array(
               '_type' => 'waContactBirthdayLocalFormatter',
               'options' => 
              array (
                'prefix' => 'birth',
              ),
            )),
            'locale' => 
            waContactBirthdayLocalFormatter::__set_state(array(
               '_type' => 'waContactBirthdayLocalFormatter',
               'options' => 
              array (
                'prefix' => 'birth',
              ),
            )),
            'list' => 
            waContactBirthdayLocalFormatter::__set_state(array(
               '_type' => 'waContactBirthdayLocalFormatter',
               'options' => 
              array (
                'prefix' => 'birth',
              ),
            )),
          ),
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waDateValidator',
      )),
    ),
     'name' => 
    array (
      'en_US' => 'Birthday',
    ),
     '_type' => 'waContactBirthdayField',
  )),
  11 => 
  waContactTextField::__set_state(array(
     'id' => 'about',
     'options' => 
    array (
      'storage' => 'info',
      'input_height' => 5,
    ),
     'name' => 
    array (
      'en_US' => 'Description',
    ),
     '_type' => 'waContactTextField',
  )),
  12 => 
  waContactPhoneField::__set_state(array(
     'id' => 'phone',
     'options' => 
    array (
      'multi' => true,
      'ext' => 
      array (
        'work' => 'work',
        'mobile' => 'mobile',
        'home' => 'home',
      ),
      'top' => true,
      'storage' => 'data',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 0 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'multi' => true,
          'ext' => 
          array (
            'work' => 'work',
            'mobile' => 'mobile',
            'home' => 'home',
          ),
          'top' => true,
          'storage' => 'data',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
      'formats' => 
      array (
        'js' => 
        waContactPhoneJsFormatter::__set_state(array(
           '_type' => 'waContactPhoneJsFormatter',
           'options' => NULL,
        )),
        'value' => 
        waContactPhoneFormatter::__set_state(array(
           '_type' => 'waContactPhoneFormatter',
           'options' => NULL,
        )),
        'html' => 
        waContactPhoneTopFormatter::__set_state(array(
           '_type' => 'waContactPhoneTopFormatter',
           'options' => NULL,
        )),
        'top' => 
        waContactPhoneTopFormatter::__set_state(array(
           '_type' => 'waContactPhoneTopFormatter',
           'options' => NULL,
        )),
      ),
      'allow_self_edit' => false,
      'required' => false,
      'unique' => false,
      'my_profile' => 2,
    ),
     'name' => 
    array (
      'en_US' => 'Phone',
    ),
     '_type' => 'waContactPhoneField',
  )),
  13 => 
  waContactStringField::__set_state(array(
     'id' => 'im',
     'options' => 
    array (
      'multi' => true,
      'type' => 'IM',
      'ext' => 
      array (
        'icq' => 'ICQ',
        'skype' => 'Skype',
        'jabber' => 'Jabber',
        'yahoo' => 'Yahoo',
        'aim' => 'AIM',
        'msn' => 'MSN',
        'telegram' => 'Telegram',
        'viber' => 'Viber',
      ),
      'formats' => 
      array (
        'top' => 
        waContactIMTopFormatter::__set_state(array(
           '_type' => 'waContactIMTopFormatter',
           'options' => NULL,
        )),
        'js' => 
        waContactIMJSFormatter::__set_state(array(
           '_type' => 'waContactIMJSFormatter',
           'options' => NULL,
        )),
      ),
      'top' => true,
      'storage' => 'data',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 0 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'multi' => true,
          'type' => 'IM',
          'ext' => 
          array (
            'icq' => 'ICQ',
            'skype' => 'Skype',
            'jabber' => 'Jabber',
            'yahoo' => 'Yahoo',
            'aim' => 'AIM',
            'msn' => 'MSN',
            'telegram' => 'Telegram',
            'viber' => 'Viber',
          ),
          'formats' => 
          array (
            'top' => 
            waContactIMTopFormatter::__set_state(array(
               '_type' => 'waContactIMTopFormatter',
               'options' => NULL,
            )),
            'js' => 
            waContactIMJSFormatter::__set_state(array(
               '_type' => 'waContactIMJSFormatter',
               'options' => NULL,
            )),
          ),
          'top' => true,
          'storage' => 'data',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
    ),
     'name' => 
    array (
      'en_US' => 'Instant messenger',
    ),
     '_type' => 'waContactStringField',
  )),
  14 => 
  waContactStringField::__set_state(array(
     'id' => 'socialnetwork',
     'options' => 
    array (
      'multi' => true,
      'type' => 'SocialNetwork',
      'ext' => 
      array (
        'facebook' => 'Facebook',
        'twitter' => 'Twitter',
        'linkedin' => 'LinkedIn',
        'vkontakte' => 'VKontakte',
      ),
      'formats' => 
      array (
        'top' => 
        waContactSocialNetworkTopFormatter::__set_state(array(
           '_type' => 'waContactSocialNetworkTopFormatter',
           'options' => NULL,
        )),
        'js' => 
        waContactSocialNetworkJSFormatter::__set_state(array(
           '_type' => 'waContactSocialNetworkJSFormatter',
           'options' => NULL,
        )),
      ),
      'domain' => 
      array (
        'facebook' => 'facebook.com',
        'vkontakte' => 'vk.com',
        'twitter' => 'twitter.com',
        'linkedin' => NULL,
      ),
      'storage' => 'data',
      'validators' => 
      waStringValidator::__set_state(array(
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'max_length' => 'Пожалуйста, не более 0 символов',
          'min_length' => 'Пожалуйста, не менее 0 символов',
        ),
         'options' => 
        array (
          'required' => false,
          'multi' => true,
          'type' => 'SocialNetwork',
          'ext' => 
          array (
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'vkontakte' => 'VKontakte',
          ),
          'formats' => 
          array (
            'top' => 
            waContactSocialNetworkTopFormatter::__set_state(array(
               '_type' => 'waContactSocialNetworkTopFormatter',
               'options' => NULL,
            )),
            'js' => 
            waContactSocialNetworkJSFormatter::__set_state(array(
               '_type' => 'waContactSocialNetworkJSFormatter',
               'options' => NULL,
            )),
          ),
          'domain' => 
          array (
            'facebook' => 'facebook.com',
            'vkontakte' => 'vk.com',
            'twitter' => 'twitter.com',
            'linkedin' => NULL,
          ),
          'storage' => 'data',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waStringValidator',
      )),
    ),
     'name' => 
    array (
      'en_US' => 'Social network',
    ),
     '_type' => 'waContactStringField',
  )),
  15 => 
  waContactAddressField::__set_state(array(
     'id' => 'address',
     'options' => 
    array (
      'multi' => true,
      'ext' => 
      array (
        'work' => 'work',
        'home' => 'home',
        'shipping' => 'shipping',
        'billing' => 'billing',
      ),
      'storage' => 'data',
      'fields' => 
      array (
        0 => 
        waContactStringField::__set_state(array(
           'id' => 'street',
           'options' => 
          array (
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
          ),
           'name' => 
          array (
            'en_US' => 'Street address',
          ),
           '_type' => 'waContactStringField',
        )),
        'city' => 
        waContactStringField::__set_state(array(
           'id' => 'city',
           'options' => 
          array (
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
          ),
           'name' => 
          array (
            'en_US' => 'City',
          ),
           '_type' => 'waContactStringField',
        )),
        'region' => 
        waContactRegionField::__set_state(array(
           'rm' => NULL,
           'id' => 'region',
           'options' => 
          array (
            'storage' => 'data',
          ),
           'name' => 
          array (
            'en_US' => 'State',
          ),
           '_type' => 'waContactRegionField',
        )),
        'zip' => 
        waContactStringField::__set_state(array(
           'id' => 'zip',
           'options' => 
          array (
            'storage' => 'data',
            'validators' => 
            waStringValidator::__set_state(array(
               'messages' => 
              array (
                'required' => 'Нужно заполнить',
                'invalid' => 'Неверно',
                'max_length' => 'Пожалуйста, не более 0 символов',
                'min_length' => 'Пожалуйста, не менее 0 символов',
              ),
               'options' => 
              array (
                'required' => false,
                'storage' => 'data',
              ),
               'errors' => 
              array (
              ),
               '_type' => 'waStringValidator',
            )),
          ),
           'name' => 
          array (
            'en_US' => 'ZIP',
          ),
           '_type' => 'waContactStringField',
        )),
        'country' => 
        waContactCountryField::__set_state(array(
           'model' => NULL,
           'validate_range' => true,
           'id' => 'country',
           'options' => 
          array (
            'defaultOption' => 'Select country',
            'storage' => 'data',
            'formats' => 
            array (
              'value' => 
              waContactCountryFormatter::__set_state(array(
                 '_type' => 'waContactCountryFormatter',
                 'options' => NULL,
              )),
            ),
          ),
           'name' => 
          array (
            'en_US' => 'Country',
          ),
           '_type' => 'waContactCountryField',
        )),
        'lng' => 
        waContactHiddenField::__set_state(array(
           'id' => 'lng',
           'options' => 
          array (
            'storage' => 'data',
          ),
           'name' => 
          array (
            'en_US' => 'Longitude',
          ),
           '_type' => 'waContactHiddenField',
        )),
        'lat' => 
        waContactHiddenField::__set_state(array(
           'id' => 'lat',
           'options' => 
          array (
            'storage' => 'data',
          ),
           'name' => 
          array (
            'en_US' => 'Latitude',
          ),
           '_type' => 'waContactHiddenField',
        )),
      ),
      'formats' => 
      array (
        'js' => 
        waContactAddressOneLineFormatter::__set_state(array(
           '_type' => 'waContactAddressOneLineFormatter',
           'options' => NULL,
        )),
        'forMap' => 
        waContactAddressForMapFormatter::__set_state(array(
           '_type' => 'waContactAddressForMapFormatter',
           'options' => NULL,
        )),
      ),
      'required' => 
      array (
      ),
    ),
     'name' => 
    array (
      'en_US' => 'Address',
    ),
     '_type' => 'waContactAddressField',
  )),
  16 => 
  waContactUrlField::__set_state(array(
     'id' => 'url',
     'options' => 
    array (
      'multi' => true,
      'ext' => 
      array (
        'work' => 'work',
        'personal' => 'personal',
      ),
      'storage' => 'data',
      'formats' => 
      array (
        'html' => 
        waContactUrlHtmlFormatter::__set_state(array(
           '_type' => 'waContactUrlHtmlFormatter',
           'options' => NULL,
        )),
        'js' => 
        waContactUrlJsFormatter::__set_state(array(
           '_type' => 'waContactUrlJsFormatter',
           'options' => NULL,
        )),
      ),
      'validators' => 
      waUrlValidator::__set_state(array(
         'options' => 
        array (
          'required' => false,
          'pattern' => '`^(https?|ftp|gopher|telnet|file|notes|ms-help):((//)|(\\\\\\\\))+([^[:punct:]]|[:#@%/;$()~_?\\+-=\\.&\\\\])*$`iu',
          'multi' => true,
          'ext' => 
          array (
            'work' => 'work',
            'personal' => 'personal',
          ),
          'storage' => 'data',
          'formats' => 
          array (
            'html' => 
            waContactUrlHtmlFormatter::__set_state(array(
               '_type' => 'waContactUrlHtmlFormatter',
               'options' => NULL,
            )),
            'js' => 
            waContactUrlJsFormatter::__set_state(array(
               '_type' => 'waContactUrlJsFormatter',
               'options' => NULL,
            )),
          ),
        ),
         'messages' => 
        array (
          'required' => 'Нужно заполнить',
          'invalid' => 'Неверно',
          'not_match' => 'Invalid URL',
        ),
         'errors' => 
        array (
        ),
         '_type' => 'waUrlValidator',
      )),
    ),
     'name' => 
    array (
      'en_US' => 'Website',
    ),
     '_type' => 'waContactUrlField',
  )),
  17 => 
  waContactLocaleField::__set_state(array(
     'locales' => NULL,
     'validate_range' => true,
     'id' => 'locale',
     'options' => 
    array (
      'storage' => 'info',
      'defaultOption' => 'Select language',
      'formats' => 
      array (
        'value' => 
        waContactLocaleFormatter::__set_state(array(
           '_type' => 'waContactLocaleFormatter',
           'options' => NULL,
        )),
        'html' => 
        waContactLocaleFormatter::__set_state(array(
           '_type' => 'waContactLocaleFormatter',
           'options' => NULL,
        )),
      ),
    ),
     'name' => 
    array (
      'en_US' => 'Language',
    ),
     '_type' => 'waContactLocaleField',
  )),
  18 => 
  waContactTimezoneField::__set_state(array(
     'timezones' => NULL,
     'validate_range' => true,
     'id' => 'timezone',
     'options' => 
    array (
      'storage' => 'info',
      'defaultOption' => 'Select time zone',
    ),
     'name' => 
    array (
      'en_US' => 'Time zone',
    ),
     '_type' => 'waContactTimezoneField',
  )),
  19 => 
  waContactCategoriesField::__set_state(array(
     'model' => NULL,
     'categories' => NULL,
     'validate_range' => true,
     'id' => 'categories',
     'options' => 
    array (
      'hrefPrefix' => '#/contacts/category/',
      'fconstructor' => 'hidden',
      'hidden' => true,
      'storage' => 'waContactCategoryStorage',
      'required' => NULL,
    ),
     'name' => 
    array (
      'en_US' => 'Categories',
    ),
     '_type' => 'waContactCategoriesField',
  )),
);
