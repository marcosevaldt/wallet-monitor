# Colunas Copiáveis - Carteiras e Transações

## Resumo das Melhorias

Adicionada funcionalidade de **cópia com um clique** para as colunas **Hash** e **Endereço** em todas as seções do sistema, seguindo um padrão consistente. As colunas agora mostram apenas o início e fim com reticências para melhor visualização, mas copiam o valor completo.

## Mudanças Implementadas

### 1. WalletResource.php (Seção de Carteiras)
- ✅ **Coluna Endereço**: Adicionado `copyable()` com mensagem personalizada
- ✅ **Visualização otimizada**: Mostra apenas 8 caracteres do início e fim
- ✅ **Mensagem de feedback**: "Endereço copiado!"
- ✅ **Duração da mensagem**: 1.5 segundos
- ✅ **Tooltip**: Mostra o valor completo ao passar o mouse

### 2. TransactionResource.php (Seção de Transações)
- ✅ **Coluna Hash**: Adicionado `copyable()` com mensagem personalizada
- ✅ **Coluna Endereço**: Adicionado `copyable()` com mensagem personalizada
- ✅ **Visualização otimizada**: Mostra apenas 8 caracteres do início e fim
- ✅ **Mensagens de feedback**: "Hash copiado!" e "Endereço copiado!"
- ✅ **Duração da mensagem**: 1.5 segundos
- ✅ **Tooltip**: Mostra o valor completo ao passar o mouse

### 3. TransactionsRelationManager.php (Transações por Carteira)
- ✅ **Coluna Hash**: Adicionado mensagens de cópia personalizadas
- ✅ **Coluna Endereço**: Adicionado mensagens de cópia personalizadas
- ✅ **Visualização otimizada**: Mesmo padrão de truncamento
- ✅ **Padronização**: Mesmo comportamento em todas as seções

## Funcionalidades Adicionadas

### Coluna Endereço da Carteira
```php
Tables\Columns\TextColumn::make('address')
    ->label('Endereço')
    ->searchable()
    ->copyable()
    ->copyMessage('Endereço copiado!')
    ->copyMessageDuration(1500)
    ->formatStateUsing(function ($state) {
        if (strlen($state) > 16) {
            return substr($state, 0, 8) . '...' . substr($state, -8);
        }
        return $state;
    })
    ->tooltip(...)
```

### Coluna Hash da Transação
```php
Tables\Columns\TextColumn::make('tx_hash')
    ->label('Hash')
    ->searchable()
    ->copyable()
    ->copyMessage('Hash copiado!')
    ->copyMessageDuration(1500)
    ->formatStateUsing(function ($state) {
        if (strlen($state) > 16) {
            return substr($state, 0, 8) . '...' . substr($state, -8);
        }
        return $state;
    })
    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
        $state = $column->getState();
        if (strlen($state) > 16) {
            // Quebrar o hash em linhas para melhor visualização
            $chunkSize = 32; // 32 caracteres por linha
            $chunks = str_split($state, $chunkSize);
            return implode("\n", $chunks);
        }
        return null;
    })
```

### Coluna Endereço da Transação
```php
Tables\Columns\TextColumn::make('address')
    ->label('Endereço')
    ->searchable()
    ->copyable()
    ->copyMessage('Endereço copiado!')
    ->copyMessageDuration(1500)
    ->formatStateUsing(function ($state) {
        if (strlen($state) > 16) {
            return substr($state, 0, 8) . '...' . substr($state, -8);
        }
        return $state;
    })
    ->tooltip(...)
```

## Formato de Visualização

### Exemplos de Exibição:
- **Endereço da Carteira**: `1A1zP1eP...7DivfNa` (em vez de `1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa`)
- **Hash da Transação**: `7520d8e4...ffc11abc` (em vez de `7520d8e494e7ffbffc11abc...`)
- **Endereço da Transação**: `1A1zP1eP...7DivfNa` (em vez de `1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa`)

### Comportamento:
- **Valores ≤ 16 caracteres**: Exibidos completos
- **Valores > 16 caracteres**: Primeiros 8 + "..." + últimos 8 caracteres
- **Cópia**: Sempre copia o valor completo
- **Tooltip**: Mostra o valor completo ao passar o mouse

### Melhoria no Tooltip da Hash:
- **Quebra automática**: Hash dividido em linhas de 32 caracteres
- **Visualização compacta**: Tooltip não sai da caixa padrão
- **Legibilidade**: Facilita a leitura de hashes longos
- **Exemplo**: 
  ```
  7520d8e494e7ffbffc11abc1234567890
  abcdef1234567890abcdef1234567890
  ```

## Onde Funciona

### 1. Seção de Carteiras
- **Rota**: `/admin/wallets`
- **Arquivo**: `WalletResource.php`
- **Funcionalidade**: Cópia de endereço com feedback visual

### 2. Seção Principal de Transações
- **Rota**: `/admin/transactions`
- **Arquivo**: `TransactionResource.php`
- **Funcionalidade**: Cópia de hash e endereço com feedback visual

### 3. Seção de Transações por Carteira
- **Rota**: `/admin/wallets/{id}/edit?activeTab=transactions`
- **Arquivo**: `TransactionsRelationManager.php`
- **Funcionalidade**: Cópia de hash e endereço com feedback visual

## Experiência do Usuário

### Antes
- ❌ Endereços e hashes não eram copiáveis
- ❌ Usuário precisava selecionar e copiar manualmente
- ❌ Sem feedback visual
- ❌ Valores longos ocupavam muito espaço na tabela

### Depois
- ✅ **Cópia com um clique** no ícone de cópia
- ✅ **Feedback visual** com mensagens personalizadas
- ✅ **Mensagem desaparece** automaticamente após 1.5 segundos
- ✅ **Visualização compacta** com formato xxxx...yyyy
- ✅ **Tooltip informativo** ao passar o mouse
- ✅ **Consistência** em todas as seções do sistema

## Benefícios

1. **Produtividade**: Cópia rápida de endereços e hashes
2. **Usabilidade**: Interface mais intuitiva e limpa
3. **Visualização**: Tabelas mais compactas e legíveis
4. **Tooltip otimizado**: Hash quebrado em linhas para melhor legibilidade
5. **Consistência**: Mesmo padrão em todas as seções
6. **Feedback**: Confirmação visual da cópia
7. **Acessibilidade**: Facilita a verificação de dados

## Padrão Implementado

Seguindo um padrão consistente em todo o sistema:
- `copyable()` - Habilita a funcionalidade de cópia
- `copyMessage()` - Mensagem personalizada de feedback
- `copyMessageDuration()` - Duração da mensagem em milissegundos
- `formatStateUsing()` - Formata a exibição (8...8 caracteres)
- `tooltip()` - Mostra o valor completo ao passar o mouse

## Arquivos Modificados

1. `app/Filament/Resources/WalletResource.php`
2. `app/Filament/Resources/TransactionResource.php`
3. `app/Filament/Resources/WalletResource/RelationManagers/TransactionsRelationManager.php`

## Teste

Para testar as funcionalidades:
1. **Carteiras**: Acesse `/admin/wallets` e teste a cópia de endereços
2. **Transações**: Acesse `/admin/transactions` e teste a cópia de hashes e endereços
3. **Transações por Carteira**: Acesse uma carteira específica e teste na aba de transações
4. Observe que todos os valores aparecem no formato `xxxx...yyyy`
5. Passe o mouse sobre um valor para ver o tooltip com o valor completo
6. Clique no ícone de cópia e verifique se a mensagem de feedback aparece
7. Cole o conteúdo copiado em qualquer editor de texto (deve ser o valor completo) 