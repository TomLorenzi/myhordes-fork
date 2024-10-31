export type VaultRequest = {
    ids: number[],
}

export type VaultEntry = {
    id: number;
}

export type VaultItemEntry = VaultEntry & {
    name: string,
    desc: string,
    icon: string,
    props: string[],
    heavy: boolean,
    deco: number|null,
    watch: number|null,
}

export type VaultStorage<V extends VaultEntry> = { [key: number]: V; }