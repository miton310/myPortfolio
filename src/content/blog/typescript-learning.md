---
title: "TypeScriptの型パズルに挑戦した記録"
description: "ユーティリティ型の理解を深めるため、type-challengesの問題を解いた学習ログです。"
category: "学習ログ"
date: 2026-05-20
tags: ["TypeScript", "学習"]
---

ユーティリティ型の理解を深めるため、type-challenges の easy 問題を一通り解きました。

## 学んだこと

- `keyof` と Mapped Types の組み合わせで `Pick` を自作できる
- `extends` による条件型は型の絞り込みに使える
- `infer` でタプルや関数型から型を取り出せる

## つまずいたところ

`readonly` 修飾子の付け外しまわりで混乱したので、Mapped Types の修飾子操作(`+readonly` / `-readonly`)を改めて整理しました。

## 次にやること

medium 問題に進みつつ、実務のコードで型定義をリファクタリングしてみる予定です。
