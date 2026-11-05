import { readFileSync } from 'node:fs'
import { getSchema } from '@tiptap/core'
import Mention from '@tiptap/extension-mention'
import StarterKit from '@tiptap/starter-kit'

const schema = getSchema([StarterKit.configure({ heading: { levels: [1, 2, 3, 4] } }), Mention])
const documents = JSON.parse(readFileSync(process.argv[2], 'utf8'))

documents.forEach((document) => {
    schema.nodeFromJSON(document).check()
})

console.log(`ok ${documents.length}`)
