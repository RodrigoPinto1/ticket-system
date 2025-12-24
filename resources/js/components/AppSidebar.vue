<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, Mail, Shield, Ticket } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const baseMainNavItems: NavItem[] = [
    {
        title: 'Tickets',
        href: '/tickets',
        icon: Ticket,
    },
    {
        title: 'Templates de E-mail',
        href: '/notification-templates',
        icon: Mail,
    },
    {
        title: 'Gestão de Cargos',
        href: '/inboxes/roles',
        icon: Shield,
    },
];

const page = usePage();
const mainNavItems = computed<NavItem[]>(() => {
    const flags = (page.props as any).authFlags || {
        isOperator: false,
        isOwner: false,
        isClient: false,
    };
    const items = [...baseMainNavItems];
    // Show templates only for operators or owners; hide for clients/others
    const isPrivileged = !!flags.isOperator || !!flags.isOwner;
    return isPrivileged
        ? items
        : items.filter(
              (i) =>
                  !['/notification-templates', '/inboxes/roles'].includes(
                      i.href,
                  ),
          );
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
