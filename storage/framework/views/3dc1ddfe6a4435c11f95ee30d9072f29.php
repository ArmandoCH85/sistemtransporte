<?php if (isset($component)) { $__componentOriginal94157f06fb7391fb58b1eb836cbd7746 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal94157f06fb7391fb58b1eb836cbd7746 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-impersonate::components.banner','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-impersonate::banner'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal94157f06fb7391fb58b1eb836cbd7746)): ?>
<?php $attributes = $__attributesOriginal94157f06fb7391fb58b1eb836cbd7746; ?>
<?php unset($__attributesOriginal94157f06fb7391fb58b1eb836cbd7746); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal94157f06fb7391fb58b1eb836cbd7746)): ?>
<?php $component = $__componentOriginal94157f06fb7391fb58b1eb836cbd7746; ?>
<?php unset($__componentOriginal94157f06fb7391fb58b1eb836cbd7746); ?>
<?php endif; ?><?php /**PATH C:\Users\arman\Herd\transporte\storage\framework\views/9f29a28cb8146bd3e12bcd2b1bf61baa.blade.php ENDPATH**/ ?>