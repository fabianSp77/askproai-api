@props(['field'])

@php
    $type = $field['type'] ?? 'text';
    $name = $field['name'] ?? '';
    $label = $field['label'] ?? '';
    $value = $field['value'] ?? '';
    $placeholder = $field['placeholder'] ?? '';
    $required = $field['required'] ?? false;
    $disabled = $field['disabled'] ?? false;
    $readonly = $field['readonly'] ?? false;
    $help = $field['help'] ?? '';
    $error = $field['error'] ?? '';
    $options = $field['options'] ?? [];
    $multiple = $field['multiple'] ?? false;
    $rows = $field['rows'] ?? 3;
@endphp

<div class="{{ $field['wrapper_class'] ?? '' }}">
    @if($label && $type !== 'checkbox' && $type !== 'radio')
        <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @switch($type)
        @case('text')
        @case('email')
        @case('password')
        @case('number')
        @case('tel')
        @case('url')
            <input type="{{ $type }}"
                   name="{{ $name }}"
                   id="{{ $name }}"
                   value="{{ old($name, $value) }}"
                   placeholder="{{ $placeholder }}"
                   {{ $required ? 'required' : '' }}
                   {{ $disabled ? 'disabled' : '' }}
                   {{ $readonly ? 'readonly' : '' }}
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 {{ $error ? 'border-red-500' : '' }}">
            @break

        @case('date')
        @case('datetime-local')
        @case('time')
            <input type="{{ $type }}"
                   name="{{ $name }}"
                   id="{{ $name }}"
                   value="{{ old($name, $value) }}"
                   {{ $required ? 'required' : '' }}
                   {{ $disabled ? 'disabled' : '' }}
                   {{ $readonly ? 'readonly' : '' }}
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 {{ $error ? 'border-red-500' : '' }}">
            @break

        @case('textarea')
            <textarea name="{{ $name }}"
                      id="{{ $name }}"
                      rows="{{ $rows }}"
                      placeholder="{{ $placeholder }}"
                      {{ $required ? 'required' : '' }}
                      {{ $disabled ? 'disabled' : '' }}
                      {{ $readonly ? 'readonly' : '' }}
                      class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 {{ $error ? 'border-red-500' : '' }}">{{ old($name, $value) }}</textarea>
            @break

        @case('select')
            <select name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                    id="{{ $name }}"
                    {{ $required ? 'required' : '' }}
                    {{ $disabled ? 'disabled' : '' }}
                    {{ $multiple ? 'multiple' : '' }}
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 {{ $error ? 'border-red-500' : '' }}">
                @if(!$multiple && !$required)
                    <option value="">{{ $placeholder ?: 'Select an option' }}</option>
                @endif
                @foreach($options as $option)
                    @if(is_array($option))
                        <option value="{{ $option['value'] }}" 
                                {{ old($name, $value) == $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @else
                        <option value="{{ $option }}" 
                                {{ old($name, $value) == $option ? 'selected' : '' }}>
                            {{ $option }}
                        </option>
                    @endif
                @endforeach
            </select>
            @break

        @case('checkbox')
            <div class="flex items-center">
                <input type="checkbox"
                       name="{{ $name }}"
                       id="{{ $name }}"
                       value="{{ $value ?: 1 }}"
                       {{ old($name, $value) ? 'checked' : '' }}
                       {{ $required ? 'required' : '' }}
                       {{ $disabled ? 'disabled' : '' }}
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="{{ $name }}" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                    {{ $label }}
                    @if($required)
                        <span class="text-red-500">*</span>
                    @endif
                </label>
            </div>
            @break

        @case('radio')
            <div class="space-y-2">
                @foreach($options as $option)
                    <div class="flex items-center">
                        <input type="radio"
                               name="{{ $name }}"
                               id="{{ $name }}_{{ is_array($option) ? $option['value'] : $option }}"
                               value="{{ is_array($option) ? $option['value'] : $option }}"
                               {{ old($name, $value) == (is_array($option) ? $option['value'] : $option) ? 'checked' : '' }}
                               {{ $required ? 'required' : '' }}
                               {{ $disabled ? 'disabled' : '' }}
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="{{ $name }}_{{ is_array($option) ? $option['value'] : $option }}" 
                               class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                            {{ is_array($option) ? $option['label'] : $option }}
                        </label>
                    </div>
                @endforeach
            </div>
            @break

        @case('toggle')
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox"
                       name="{{ $name }}"
                       id="{{ $name }}"
                       value="{{ $value ?: 1 }}"
                       {{ old($name, $value) ? 'checked' : '' }}
                       {{ $disabled ? 'disabled' : '' }}
                       class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300">{{ $label }}</span>
            </label>
            @break

        @case('file')
            <input type="file"
                   name="{{ $name }}"
                   id="{{ $name }}"
                   {{ $required ? 'required' : '' }}
                   {{ $disabled ? 'disabled' : '' }}
                   {{ $multiple ? 'multiple' : '' }}
                   accept="{{ $field['accept'] ?? '' }}"
                   class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400">
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">{{ $field['hint'] ?? 'SVG, PNG, JPG or GIF (MAX. 800x400px)' }}</p>
            @break

        @case('color')
            <input type="color"
                   name="{{ $name }}"
                   id="{{ $name }}"
                   value="{{ old($name, $value ?: '#000000') }}"
                   {{ $required ? 'required' : '' }}
                   {{ $disabled ? 'disabled' : '' }}
                   class="p-1 h-10 w-14 block bg-white border border-gray-200 cursor-pointer rounded-lg disabled:opacity-50 disabled:pointer-events-none dark:bg-gray-800 dark:border-gray-700">
            @break

        @case('range')
            <input type="range"
                   name="{{ $name }}"
                   id="{{ $name }}"
                   value="{{ old($name, $value ?: 50) }}"
                   min="{{ $field['min'] ?? 0 }}"
                   max="{{ $field['max'] ?? 100 }}"
                   step="{{ $field['step'] ?? 1 }}"
                   {{ $disabled ? 'disabled' : '' }}
                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
            @if(isset($field['show_value']) && $field['show_value'])
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>{{ $field['min'] ?? 0 }}</span>
                    <span id="{{ $name }}_value">{{ old($name, $value ?: 50) }}</span>
                    <span>{{ $field['max'] ?? 100 }}</span>
                </div>
                <script>
                    document.getElementById('{{ $name }}').addEventListener('input', function(e) {
                        document.getElementById('{{ $name }}_value').textContent = e.target.value;
                    });
                </script>
            @endif
            @break

        @default
            <input type="text"
                   name="{{ $name }}"
                   id="{{ $name }}"
                   value="{{ old($name, $value) }}"
                   placeholder="{{ $placeholder }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
    @endswitch

    @if($help)
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $help }}</p>
    @endif

    @if($error)
        <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $error }}</p>
    @endif

    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
    @enderror
</div>