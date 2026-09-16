<template>
  <CareShell
    title="Perfis e decisões compartilhadas"
    subtitle="Cada participante responde pelos próprios registros; ninguém decide em nome de outra pessoa."
  >
    <div class="care-grid">
      <AppCard v-for="(description, role) in profiles" :key="role" :title="role"
        ><p>{{ description }}</p></AppCard
      >
    </div>
    <AppCard title="Aceite, recusa e histórico"
      ><p>
        Tarefas, compromissos e alimentação dependem do aceite dos demais
        responsáveis pelo assistido. Outros participantes afetados também devem
        aceitar. Uma recusa exige motivo. Alterações e cancelamentos de
        registros acordados dependem de novo aceite; a versão anterior continua
        válida até a decisão.
      </p>
      <p>
        Cada pessoa altera seus próprios registros e documentos. Responsáveis
        não podem remover ou suspender outro responsável unilateralmente.
        Observadores nunca editam nem assumem obrigações.
      </p></AppCard
    >
    <AppCard title="Acesso por assistido"
      ><p>
        O perfil no grupo não libera todos os assistidos. Vincule responsáveis
        ao assistido e conceda somente as áreas necessárias aos terceiros
        cuidadores ou observadores. Responsáveis vinculados têm acesso às áreas
        de cuidados e participam das decisões; seus acessos não podem ser
        reduzidos unilateralmente.
      </p>
      <RouterLink class="btn btn--primary" :to="{ name: 'recipients' }"
        >Gerenciar assistidos</RouterLink
      ></AppCard
    >
  </CareShell>
</template>
<script setup>
import CareShell from "@/components/care/CareShell.vue";
import { AppCard } from "@/components/ui";
const profiles = {
  Responsável:
    "Organiza os próprios cuidados e delibera sobre propostas que afetam o cuidado compartilhado. Não tem autoridade sobre outro responsável.",
  Cuidador:
    "Terceiro contratado ou autorizado a executar tarefas e serviços de cuidados, com acesso somente às áreas concedidas.",
  Observador:
    "Acompanha e visualiza somente as áreas autorizadas, sem incluir, alterar ou decidir registros.",
};
</script>
