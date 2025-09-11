@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    
      <BlockBreadcrumb
        title="Update Forms (CRUD)"
        description={{ `Get started with a collection of CRUD layouts based on the "update" action featuring form elements like input text fields, date pickers, file upload, and more."` }}
      ></BlockBreadcrumb>
      <BlockSection
        title="Default form"
        description="Use this free example of a CRUD form layout to update existing data sets featuring text fields inputs, select boxes, and more."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/default.tsx"
      >
        <DefaultUpdateForm ></DefaultUpdateForm>
      </BlockSection>
      <BlockSection
        title="Update event form"
        description="This example can be used to show multiple input fields such as date pickers, WYSIWYG editors, and text input fields to update existing data sets inside a CRUD layout."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/alert-info.tsx"
      >
        <UpdateEventForm ></UpdateEventForm>
      </BlockSection>
      <BlockSection
        title="Update user form"
        description="Use this example a responsive CRUD form layout to update an existing user from your database featuring text, password, and textarea input fields."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/user-profile.tsx"
      >
        <UpdateUserForm ></UpdateUserForm>
      </BlockSection>
      <BlockSection
        title="Update form with accordion"
        description="Use this example of a CRUD form layout to update an existing product data entry from your database featuring multiple input fields elements, dropzone file upload, and more."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/user-switch.tsx"
      >
        <UpdateFormWithAccordion ></UpdateFormWithAccordion>
      </BlockSection>
      <BlockSection
        title="Advanced update user form"
        description="Use this example to update an existing user from your database with advanced input fields such as the file upload, WISYWIG editor, and more."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/notifications.tsx"
      >
        <AdvancedUpdateUserForm ></AdvancedUpdateUserForm>
      </BlockSection>
    
  
@endsection