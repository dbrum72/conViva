<template>
  <PublicHeader />
  <main class="care-landing">
    <span class="care-pill">Para famílias e redes de apoio</span>
    <h1>Cuidar fica mais leve<br />quando é compartilhado.</h1>
    <p>
      Organize os cuidados de crianças, pessoas adultas e pets. Uma rotina
      clara, com cada pessoa sabendo como ajudar.
    </p>
    <RouterLink class="btn btn--primary" :to="{ name: 'register' }"
      >Criar meu grupo de cuidados</RouterLink
    >
    <section id="recursos" class="care-grid">
      <AppCard title="Todos juntos"
        >Convide familiares e cuidadores, com acesso definido para cada
        assistido.</AppCard
      ><AppCard title="Uma rotina organizada"
        >Agenda, tarefas, medicamentos, vacinas e registros em um só
        lugar.</AppCard
      ><AppCard title="Tudo no seu lugar"
        >Documentos privados e despesas compartilhadas, com histórico de quem
        cuidou.</AppCard
      >
    </section>
    <section id="beneficios">
      <h2>Mais presença. Menos desencontros.</h2>
      <p>
        Compartilhe apenas o necessário e mantenha sua rede de apoio informada.
      </p>
    </section>
  </main>
  <PublicFooter />
</template>
<script setup>
import PublicHeader from "@/components/public/PublicHeader.vue";
import PublicFooter from "@/components/public/PublicFooter.vue";
import { AppCard } from "@/components/ui";
</script>
