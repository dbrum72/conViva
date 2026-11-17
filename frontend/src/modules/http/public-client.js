import axios from "axios";

import { httpConfig } from "./config.js";

const publicApiClient = axios.create(httpConfig);

export default publicApiClient;
