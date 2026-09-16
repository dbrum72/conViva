# AppFileUpload

Componente composto para seleção e gerenciamento de arquivos.

`AppFileUpload` mantém o `v-model` sempre como um array de objetos `File`, mesmo quando `multiple=false`.

## Importação

```js
import { AppFileUpload } from "@/components/forms";
```

## Uso básico

```vue
<script setup>
import { ref } from "vue";

import { AppFileUpload } from "@/components/forms";

const files = ref([]);
</script>

<template>
  <AppFileUpload
    v-model="files"
    id="document"
    name="document"
    label="Documento"
  />
</template>
```

## Contrato do v-model

O valor público é sempre:

```ts
File[]
```

Sem arquivo:

```js
[];
```

Arquivo único:

```js
[file];
```

Múltiplos arquivos:

```js
[fileA, fileB];
```

Mesmo quando `multiple=false`, o componente não alterna entre `File` e `File[]`.

## Imutabilidade

O componente não modifica `modelValue` diretamente.

Ao adicionar arquivos:

```js
[...modelValue, ...filesToAdd];
```

Ao remover:

```js
modelValue.filter(...)
```

Toda alteração produz uma nova referência de array.

## Arquivo único

Por padrão:

```js
multiple = false;
```

Uma nova seleção substitui a anterior.

Exemplo:

```text
antes:
[contrato.pdf]

nova seleção:
peticao.pdf

resultado:
[peticao.pdf]
```

Se o seletor fornecer mais de um arquivo quando `multiple=false`, apenas o primeiro válido é aceito e os demais são rejeitados com:

```text
reason = "max-files"
```

## Múltiplos arquivos

```vue
<AppFileUpload v-model="files" multiple />
```

Novos arquivos válidos são acrescentados aos existentes.

## Props

| Prop          | Tipo      | Padrão                          | Descrição                           |
| ------------- | --------- | ------------------------------- | ----------------------------------- |
| `modelValue`  | `Array`   | `[]`                            | Arquivos selecionados               |
| `id`          | `String`  | `undefined`                     | Identificador base                  |
| `name`        | `String`  | `undefined`                     | Nome do input                       |
| `label`       | `String`  | `''`                            | Label do campo                      |
| `hint`        | `String`  | `''`                            | Texto auxiliar                      |
| `error`       | `String`  | `''`                            | Mensagem de erro                    |
| `required`    | `Boolean` | `false`                         | Indica seleção obrigatória          |
| `disabled`    | `Boolean` | `false`                         | Desabilita o componente             |
| `accept`      | `String`  | `''`                            | Tipos aceitos pelo input nativo     |
| `multiple`    | `Boolean` | `false`                         | Permite múltiplos arquivos          |
| `maxFiles`    | `Number`  | `undefined`                     | Quantidade máxima de arquivos       |
| `maxFileSize` | `Number`  | `undefined`                     | Tamanho máximo por arquivo em bytes |
| `browseLabel` | `String`  | `'Selecionar arquivo'`          | Texto do botão de seleção           |
| `removeLabel` | `String`  | `'Remover arquivo'`             | Texto da ação de remoção            |
| `emptyText`   | `String`  | `'Nenhum arquivo selecionado.'` | Texto exibido sem arquivos          |

## Eventos

| Evento              | Payload            | Descrição                         |
| ------------------- | ------------------ | --------------------------------- |
| `update:modelValue` | `File[]`           | Atualiza os arquivos selecionados |
| `select`            | `File[]`           | Arquivos aceitos naquela seleção  |
| `remove`            | `File`             | Arquivo removido                  |
| `reject`            | objeto de rejeição | Arquivo rejeitado                 |
| `focus`             | `FocusEvent`       | Input recebeu foco                |
| `blur`              | `FocusEvent`       | Input perdeu foco                 |

## Evento reject

O payload segue:

```js
{
  file,
  reason,
}
```

Os motivos atuais são:

```text
max-file-size
max-files
```

Exemplo:

```js
{
  file,
  reason: 'max-file-size',
}
```

## accept

`accept` é encaminhado ao input nativo.

Exemplo:

```vue
<AppFileUpload accept=".pdf,application/pdf" />
```

Ou:

```vue
<AppFileUpload accept="image/*" />
```

Nesta versão, o componente não implementa parser próprio para `accept`.

A filtragem de MIME/extensão é delegada ao seletor nativo do navegador.

Isso significa que `accept` deve ser considerado uma orientação do cliente, não uma fronteira de segurança.

O backend deve validar novamente o arquivo recebido.

## maxFileSize

A unidade é:

```text
bytes
```

Exemplo de 5 MiB:

```js
5 * 1024 * 1024;
```

Uso:

```vue
<AppFileUpload :max-file-size="5 * 1024 * 1024" />
```

Cada arquivo é validado individualmente.

Arquivos acima do limite emitem:

```text
reject → max-file-size
```

Arquivos válidos da mesma seleção continuam sendo aceitos.

## maxFiles

```vue
<AppFileUpload multiple :max-files="3" />
```

O limite considera:

```text
arquivos já existentes
+
novos arquivos selecionados
```

Exemplo:

```text
modelValue atual: 2 arquivos
maxFiles: 3
nova seleção: 2 arquivos
```

Resultado:

```text
1 aceito
1 rejeitado
```

O excedente emite:

```text
reason = "max-files"
```

## Remoção

Cada arquivo renderizado possui uma ação de remoção.

Ao remover:

```js
emit("update:modelValue", nextValue);
emit("remove", file);
```

O array original não é mutado.

## Disabled

Quando:

```vue
disabled
```

o componente:

- desabilita o input nativo;
- desabilita o botão de seleção;
- desabilita os botões de remoção;
- impede remoção programática pela função interna.

## Required

Quando:

```vue
required
```

o input nativo recebe `required` apenas enquanto:

```js
modelValue.length === 0;
```

Após existir pelo menos um arquivo, o atributo deixa de ser necessário no input.

Isso mantém a semântica nativa alinhada ao estado externo do componente.

## Hint

```vue
<AppFileUpload hint="Arquivos PDF de até 5 MB." />
```

Quando não há erro, o hint é associado ao input através de:

```text
aria-describedby
```

## Erro

```vue
<AppFileUpload error="Selecione um arquivo válido." />
```

Quando existe erro:

- o hint é ocultado;
- `aria-describedby` referencia o erro;
- `aria-invalid="true"` é aplicado ao input.

## Input nativo

O componente mantém:

```html
<input type="file" />
```

no DOM.

Ele é visualmente ocultado, mas continua sendo o mecanismo real de seleção.

O botão customizado apenas executa:

```js
input.click();
```

Isso preserva o seletor nativo do navegador.

## Selecionar novamente o mesmo arquivo

Após cada seleção, o componente limpa o valor interno do input:

```js
input.value = "";
```

Isso permite selecionar novamente o mesmo arquivo em uma interação posterior.

## IDs

Quando:

```text
id="attachments"
```

o input recebe:

```text
attachments-input
```

O hint:

```text
attachments-hint
```

O erro:

```text
attachments-error
```

## Lista de arquivos

Arquivos selecionados são apresentados com:

```text
nome
tamanho
ação de remover
```

Exemplo:

```text
contrato.pdf
2.0 MB
Remover arquivo
```

## Formatação de tamanho

A apresentação utiliza:

```text
B
KB
MB
```

Exemplos:

```text
512 B
2.0 KB
2.0 MB
```

Essa formatação é apenas visual e não modifica `File.size`.

## Chave dos arquivos

A chave visual combina:

```text
name
size
lastModified
index
```

Ela é utilizada somente para renderização da lista.

## Segurança

`AppFileUpload` é uma camada de interface.

Ele não substitui validação no servidor.

O backend deve validar novamente, conforme o domínio da aplicação:

```text
MIME real
extensão
tamanho
quantidade
conteúdo
permissão
nome do arquivo
armazenamento
```

Não confie exclusivamente em:

```text
accept
File.type
nome/extensão fornecidos pelo navegador
```

## Drag-and-drop

A versão atual não implementa dropzone.

Isso é intencional.

A primeira versão consolida:

```text
seleção nativa
multiple
accept
maxFiles
maxFileSize
remoção
eventos
acessibilidade
```

Drag-and-drop pode ser adicionado futuramente sem alterar o contrato fundamental `File[]`.

## Preview

A versão atual também não cria previews de imagens ou PDFs.

Essa responsabilidade poderá pertencer futuramente a uma camada especializada, evitando carregar lógica de preview para todo tipo de upload.

## Boas práticas

Para arquivo único:

```vue
<AppFileUpload v-model="files" label="Documento" />
```

Para anexos:

```vue
<AppFileUpload v-model="files" label="Anexos" multiple />
```

Para documentos controlados:

```vue
<AppFileUpload
  v-model="files"
  label="Documentos"
  accept=".pdf,application/pdf"
  multiple
  :max-files="5"
  :max-file-size="5 * 1024 * 1024"
/>
```

## Playground

Consulte:

```text
Playground → Forms → AppFileUpload
```

Arquivo:

```text
src/views/playground/forms/FileUploadPlayground.vue
```

## Testes

Os testes estão em:

```text
tests/components/forms/AppFileUpload.spec.js
```

A cobertura atual inclui:

```text
input file
label
estado vazio
accept
multiple
arquivo único
substituição
múltiplos arquivos
imutabilidade
maxFiles
maxFileSize
rejeição individual
renderização de arquivos
remoção
disabled
required
hint
erro
aria-describedby
aria-invalid
focus
blur
formatação de tamanho
```
