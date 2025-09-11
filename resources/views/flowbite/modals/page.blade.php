@props([
    'title' => '',
    'description' => '',
    'items' => [],
    'class' => ''
])

@extends('layouts.app')

@section('content')

    
      <BlockBreadcrumb
        title="Delete Confirm (CRUD)"
        description="Get started with a collection of delete confirmation modal components based on the CRUD layout to make sure the user is ready to remove a selected item."
      ></BlockBreadcrumb>
      <BlockSection
        title="Default delete confirmation modal"
        description="Use this free example of a modal component coded with Tailwind CSS to confirm with the user before deleting an item from the database."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/default.tsx"
      >
        <DefaultDeleteConfirmationModal ></DefaultDeleteConfirmationModal>
      </BlockSection>
      <BlockSection
        title="Delete confirmation with items list"
        description="This example can be used to show the list of items that you are about to delete from the database inside a modal component."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/alert-info.tsx"
      >
        <DeleteConfirmationWithItemsList ></DeleteConfirmationWithItemsList>
      </BlockSection>
      <BlockSection
        title="Confirm delete with alert"
        description="Use this example to show an alert message inside the delete confirmation modal component to reveal the consequences of removing an item from the database."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/user-profile.tsx"
      >
        <ConfirmDeleteWithAlert ></ConfirmDeleteWithAlert>
      </BlockSection>
      <BlockSection
        title="Delete confirmation with icon"
        description="This example of a modal component can be used to show a descriptive icon and a warning message before proceeding with an item removal from the database."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/user-switch.tsx"
      >
        <DeleteConfirmationWithIcon ></DeleteConfirmationWithIcon>
      </BlockSection>
      <BlockSection
        title="Delete confirmation with input field"
        description="Use this example to double check a delete action by typing the name of the item inside a text input field."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/notifications.tsx"
      >
        <DeleteConfirmationWithInputField ></DeleteConfirmationWithInputField>
      </BlockSection>
      <BlockSection
        title="Confirm delete with checkbox"
        description="Use this example to require a checkbox selection from the user before proceeding with the deletion of an item from the database."
        githubLink="https://github.com/themesberg/flowbite-react-blocks/blob/main/pages/application-ui/side-navigation/projects-team-switch.tsx"
      >
        <ConfirmDeleteWithCheckbox ></ConfirmDeleteWithCheckbox>
      </BlockSection>
    
  
@endsection