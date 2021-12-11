import Ajax from "./ajax";
import HTML from "./html";
import Client from "./client";

export interface Global {
    ajax: Ajax,
    html: HTML,
    client: Client,
}

export interface Const {
    errors: object,
    taptut: string
}