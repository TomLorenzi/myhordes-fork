import Ajax from "./ajax";
import HTML from "./html";
import Client from "./client";
import Components from "./react";

export interface Global {
    ajax: Ajax,
    html: HTML,
    client: Client,
    components: Components,
}

export interface Const {
    langs: object,
    errors: object,
    taptut: string
}