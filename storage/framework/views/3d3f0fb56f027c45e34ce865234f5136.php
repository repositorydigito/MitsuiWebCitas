<!--[if BLOCK]><![endif]--><?php if($paginator->hasPages()): ?>
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col items-center space-y-3">
        
        <div class="text-sm text-gray-700 dark:text-gray-400">
            <span>Mostrando</span>
            <!--[if BLOCK]><![endif]--><?php if($paginator->firstItem()): ?>
                <span class="font-medium"><?php echo e($paginator->firstItem()); ?></span>
                <span>a</span>
                <span class="font-medium"><?php echo e($paginator->lastItem()); ?></span>
            <?php else: ?>
                <span class="font-medium"><?php echo e($paginator->count()); ?></span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <span>de</span>
            <span class="font-medium"><?php echo e($paginator->total()); ?></span>
            <span>resultados</span>
        </div>

        
        <div class="flex items-center space-x-1">
            
            <!--[if BLOCK]><![endif]--><?php if($paginator->onFirstPage()): ?>
                <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-md leading-5 dark:bg-gray-800 dark:border-gray-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            <?php else: ?>
                <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:active:bg-gray-700 dark:focus:border-primary-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

            
            <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                
                <!--[if BLOCK]><![endif]--><?php if(is_string($element)): ?>
                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300"><?php echo e($element); ?></span>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                
                <!--[if BLOCK]><![endif]--><?php if(is_array($element)): ?>
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <!--[if BLOCK]><![endif]--><?php if($page == $paginator->currentPage()): ?>
                            <span aria-current="page" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-bold text-white bg-primary-600 border border-primary-600 cursor-default leading-5 shadow-sm"><?php echo e($page); ?></span>
                        <?php else: ?>
                            <a href="<?php echo e($url); ?>" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:text-gray-200 dark:hover:bg-gray-700 dark:active:bg-gray-700 dark:focus:border-primary-700" aria-label="Go to page <?php echo e($page); ?>">
                                <?php echo e($page); ?>

                            </a>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->

            
            <!--[if BLOCK]><![endif]--><?php if($paginator->hasMorePages()): ?>
                <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" class="relative inline-flex items-center px-3 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:active:bg-gray-700 dark:focus:border-primary-700">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            <?php else: ?>
                <span class="relative inline-flex items-center px-3 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-md leading-5 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        </div>
    </nav>
<?php endif; ?><!--[if ENDBLOCK]><![endif]--><?php /**PATH /var/www/projects/mitsui/resources/views/custom-pagination.blade.php ENDPATH**/ ?>