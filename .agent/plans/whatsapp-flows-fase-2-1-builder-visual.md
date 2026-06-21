# Fase 2.1 - Builder Visual de Flow

> Operação: WhatsApp Flows na Cloud API  
> Objetivo: transformar o editor técnico em experiência para usuário leigo  
> Data: 14 de abril de 2026

---

## 1. Motivo da fase

O módulo inicial de Flow já permitia salvar `flow_json`, mas isso ainda era técnico demais para o usuário final.

A partir desta fase:

- o usuário comum pode montar um Flow visualmente;
- o sistema gera o JSON por trás;
- o JSON manual continua disponível em aba avançada;
- estruturas avançadas já existentes permanecem preservadas.

---

## 2. O que entrou

- aba `Visual builder`
- aba `Advanced JSON`
- modelos prontos:
  - captura de lead
  - contato
  - pesquisa
  - qualificação de serviços
- construtor de campos com tipos:
  - texto curto
  - texto longo
  - escolha única
  - escolha múltipla
- preview imediato do JSON gerado
- fallback seguro para Flows já existentes que não cabem no builder visual

---

## 3. Decisão de produto

O builder visual desta fase cobre o caso mais comum:

- Flow de tela única
- cadastro
- pesquisa
- qualificação

Casos mais complexos continuam no modo avançado até a próxima evolução.

---

## 4. Preservação do sistema

- nenhum fluxo atual de envio foi alterado
- nenhum webhook foi alterado
- nenhuma integração Meta foi alterada
- nenhum módulo antigo de WhatsApp foi alterado

Esta fase muda apenas a UX do módulo administrativo local de Flow.
