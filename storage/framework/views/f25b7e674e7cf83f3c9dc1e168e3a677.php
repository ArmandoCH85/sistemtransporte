<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['style', 'display', 'fixed', 'position']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['style', 'display', 'fixed', 'position']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php if(app('impersonate')->isImpersonating()): ?>

<?php
$display = $display ?? Filament\Facades\Filament::getUserName(Filament\Facades\Filament::auth()->user());
$fixed = $fixed ?? config('filament-impersonate.banner.fixed');
$position = $position ?? config('filament-impersonate.banner.position');
$borderPosition = $position === 'top' ? 'bottom' : 'top';

$style = $style ?? config('filament-impersonate.banner.style');
$styles = config('filament-impersonate.banner.styles');
$default = $style === 'auto' ? 'light' : $style;
$flipped = $default === 'dark' ? 'light' : 'dark';
?>

<style>
    :root {
        --impersonate-banner-height: 50px;

        --impersonate-light-bg-color: <?php echo e($styles['light']['background']); ?>;
        --impersonate-light-text-color: <?php echo e($styles['light']['text']); ?>;
        --impersonate-light-border-color: <?php echo e($styles['light']['border']); ?>;
        --impersonate-light-button-bg-color: <?php echo e(implode(',', sscanf($styles['dark']['background'], "#%02x%02x%02x"))); ?>;
        --impersonate-light-button-text-color: <?php echo e($styles['dark']['text']); ?>;

        --impersonate-dark-bg-color: <?php echo e($styles['dark']['background']); ?>;
        --impersonate-dark-text-color: <?php echo e($styles['dark']['text']); ?>;
        --impersonate-dark-border-color: <?php echo e($styles['dark']['border']); ?>;
        --impersonate-dark-button-bg-color: <?php echo e(implode(',', sscanf($styles['light']['background'], "#%02x%02x%02x"))); ?>;
        --impersonate-dark-button-text-color: <?php echo e($styles['light']['text']); ?>;
    }
    html {
        margin-<?php echo e($position); ?>: var(--impersonate-banner-height);
    }


    #impersonate-banner {
        position: <?php echo e($fixed ? 'fixed' : 'absolute'); ?>;
        height: var(--impersonate-banner-height);
        <?php echo e($position); ?>: 0;
        width: 100%;
        display: flex;
        column-gap: 20px;
        justify-content: center;
        align-items: center;
        background-color: var(--impersonate-<?php echo e($default); ?>-bg-color);
        color: var(--impersonate-<?php echo e($default); ?>-text-color);
        border-<?php echo e($borderPosition); ?>: 1px solid var(--impersonate-<?php echo e($default); ?>-border-color);
        z-index: 45;
    }

    <?php if($style === 'auto'): ?>
    .dark #impersonate-banner {
        background-color: var(--impersonate-dark-bg-color);
        color: var(--impersonate-dark-text-color);
        border-<?php echo e($borderPosition); ?>: 1px solid var(--impersonate-dark-border-color);
    }
    <?php endif; ?>

    #impersonate-banner a {
        display: block;
        padding: 4px 20px;
        border-radius: 5px;
        background-color: rgba(var(--impersonate-<?php echo e($default); ?>-button-bg-color), 0.7);
        color: var(--impersonate-<?php echo e($default); ?>-button-text-color);
    }

    <?php if($style === 'auto'): ?>
    .dark #impersonate-banner a {
        background-color: rgba(var(--impersonate-dark-button-bg-color), 0.7);
        color: var(--impersonate-dark-button-text-color);
    }
    <?php endif; ?>

    #impersonate-banner a:hover {
        background-color: rgb(var(--impersonate-<?php echo e($default); ?>-button-bg-color));
    }

    <?php if($style === 'auto'): ?>
    .dark #impersonate-banner a:hover {
        background-color: rgb(var(--impersonate-dark-button-bg-color));
    }
    <?php endif; ?>

    <?php if($fixed): ?>
    div.fi-layout > aside.fi-sidebar {
        height: calc(100vh - var(--impersonate-banner-height));
    }

    <?php if($position === 'top'): ?>
    .fi-topbar {
        top: var(--impersonate-banner-height);
    }
    div.fi-layout > aside.fi-sidebar {
        top: var(--impersonate-banner-height);
    }
    <?php endif; ?>

    <?php else: ?>
    div.fi-layout > aside.fi-sidebar {
        padding-bottom: var(--impersonate-banner-height);
    }
    <?php endif; ?>

    @media print{
        aside, body {
            margin-top: 0;
        }

        #impersonate-banner {
            display: none;
        }
    }
</style>

<div id="impersonate-banner">
    <div>
        <?php echo e(__('filament-impersonate::banner.impersonating')); ?> <strong><?php echo e($display); ?></strong>
    </div>

    <a href="<?php echo e(route('filament-impersonate.leave')); ?>"><?php echo e(__('filament-impersonate::banner.leave')); ?></a>
</div>
<?php endif; ?>
<?php /**PATH C:\Users\arman\Herd\transporte\vendor\stechstudio\filament-impersonate\src\/../resources/views/components/banner.blade.php ENDPATH**/ ?>