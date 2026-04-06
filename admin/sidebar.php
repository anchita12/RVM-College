<?php
include("script/settings.php");
include("menu_helper.php");

/* -------- Render Menu -------- */
function renderMenu($items, $level = 1) {

    foreach ($items as $item) {

        $hasChild = !empty($item['children']);
        $menuKey  = 'menu_'.$item['id']; // ✅ UNIQUE & SAFE

        $isParent = ($level === 1);

        $textColor = $isParent ? 'text-gray-800 font-medium' : 'text-gray-600';
        $hoverBg   = $isParent ? 'hover:bg-gray-200' : 'hover:bg-gray-100';
        $iconColor = $isParent ? 'text-blue-600' : 'text-gray-400';

        echo '<div>';

        /* MENU LINK */
        echo '<a href="'.($item['url'] ?: '#').'"
            @click="'.(
                $hasChild && $isParent
                ? "openMenu = (openMenu === '$menuKey' ? null : '$menuKey'); \$event.preventDefault();"
                : ($hasChild ? "\$event.preventDefault();" : '')
            ).'"
            :class="{
                \'bg-gray-200 text-black\': openMenu === \''.$menuKey.'\'
            }"
            class="flex items-center gap-3 p-2 mt-1 rounded-md transition-all
                   '.$textColor.' '.$hoverBg.'">';

        /* ICON */
       
        /* ARROW */
        if ($hasChild && $isParent) {
            echo '<svg class="w-4 h-4 ml-auto transition-transform duration-300"
                :class="{\'rotate-180\': openMenu === \''.$menuKey.'\'}"
                fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 9l-7 7-7-7"/>
            </svg>';
        }

        echo '</a>';

        /* CHILD MENU */
        if ($hasChild) {
            echo '<div x-show="'.($isParent ? "openMenu === '$menuKey'" : 'true').'"
                x-transition
                class="ml-6 mt-1 rounded-md bg-gray-50">';
            renderMenu($item['children'], $level + 1);
            echo '</div>';
        }

        echo '</div>';
    }
}

/* -------- Sidebar -------- */
function sidebar($db) {

    $menuTree = getMenuTree(0, $db);
?>
<aside class="flex-shrink-0 hidden w-64 bg-white border-r md:block">
    <div class="flex flex-col h-full">

        <!-- ✅ SINGLE ACCORDION STATE -->
        <nav x-data="{ openMenu: null }"
             class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">

            <?php renderMenu($menuTree); ?>

        </nav>
    </div>
</aside>
<?php
}

?>
