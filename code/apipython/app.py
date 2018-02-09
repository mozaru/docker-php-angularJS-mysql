from bottle import route, run

import usuarios
import login

if __name__ == '__main__':
    run(host='0.0.0.0', port=8000, debug=True)
